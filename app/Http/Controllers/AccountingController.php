<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BankReconciliation;
use App\Models\Budget;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ReceiptPayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function index()
    {
        [$fy, $between] = $this->fyWindow();

        return view('accounting.index', [
            'fy' => $fy,
            'totals' => $this->periodTotals($between),
            'recentEntries' => JournalEntry::with('lines')->latest('entry_date')->orderByDesc('id')->take(5)->get(),
        ]);
    }

    public function accounts(Request $request)
    {
        return view('accounting.accounts', [
            'accounts' => Account::withSum('journalLines as total_debit', 'debit')
                ->withSum('journalLines as total_credit', 'credit')
                ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
                ->orderBy('code')->get(),
            'types' => Account::types(),
        ]);
    }

    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
        ]);

        Account::create($data);
        AuditLog::record('Created account', 'Financial Accounting', "{$data['code']} — {$data['name']}");

        return redirect()->route('accounting.accounts')->with('success', "Account {$data['code']} — {$data['name']} created successfully.");
    }

    public function updateAccount(Request $request, Account $account)
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:accounts,code,'.$account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,income,expense',
        ]);

        $account->update($data);
        AuditLog::record('Updated account', 'Financial Accounting', "{$account->code} — {$account->name}");

        return redirect()->route('accounting.accounts')->with('success', 'Account updated successfully.');
    }

    public function destroyAccount(Account $account)
    {
        if ($account->journalLines()->exists()) {
            return back()->with('error', 'This account has journal activity and cannot be deleted.');
        }

        AuditLog::record('Deleted account', 'Financial Accounting', "{$account->code} — {$account->name}");
        $account->delete();

        return back()->with('success', 'Account deleted successfully.');
    }

    public function journal()
    {
        [$fy, $between] = $this->fyWindow();

        $entries = JournalEntry::with(['lines.account'])
            ->when($fy, fn ($q) => $q->whereBetween('entry_date', $between))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate(10);

        return view('accounting.journal', [
            'fy' => $fy,
            'entries' => $entries,
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function createJournal()
    {
        return view('accounting.journal-create', [
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function storeJournal(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
            'status' => 'required|in:posted,draft',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $totalDebit = collect($data['lines'])->sum(fn ($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($data['lines'])->sum(fn ($l) => (float) ($l['credit'] ?? 0));

        if ($totalDebit <= 0 && $totalCredit <= 0) {
            return back()->withInput()->with('error', 'Enter at least one debit or credit amount.');
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            return back()->withInput()->with('error', sprintf(
                'Entry is not balanced: Debits TZS %s vs Credits TZS %s. Double entry requires equal totals.',
                number_format($totalDebit, 2), number_format($totalCredit, 2)
            ));
        }

        $entry = DB::transaction(function () use ($data, $totalDebit) {
            $entry = JournalEntry::create([
                'entry_no' => JournalEntry::nextEntryNo(),
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'],
                'created_by' => auth()->user()?->name ?? 'Daniel Mwinuka',
            ]);

            foreach ($data['lines'] as $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);
                if ($debit == 0 && $credit == 0) {
                    continue;
                }
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }

            return $entry;
        });

        AuditLog::record('Posted journal entry', 'Financial Accounting',
            "{$entry->entry_no} on {$data['entry_date']} — Dr/Cr TZS ".number_format($totalDebit, 2));

        return redirect()->route('accounting.journal')
            ->with('success', "Journal entry {$entry->entry_no} recorded successfully (balanced at TZS ".number_format($totalDebit, 2).").");
    }

    public function destroyJournal(JournalEntry $entry)
    {
        AuditLog::record('Deleted journal entry', 'Financial Accounting',
            "{$entry->entry_no} — ".number_format((float) $entry->lines()->sum('debit'), 2));
        $no = $entry->entry_no;
        $entry->delete();

        return back()->with('success', "Journal entry {$no} deleted successfully.");
    }

    public function trialBalance()
    {
        [$fy, $between] = $this->fyWindow();

        $rows = $this->trialBalanceRows($between);

        return view('accounting.trial-balance', [
            'fy' => $fy,
            'rows' => $rows,
            'totals' => [
                'debit' => $rows->sum('debit'),
                'credit' => $rows->sum('credit'),
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        [$fy, $between] = $this->fyWindow();
        $accountId = $request->query('account');

        $account = $accountId ? Account::findOrFail($accountId) : null;

        $lines = collect();
        $running = 0;

        if ($account) {
            $lines = JournalLine::with('entry')
                ->where('account_id', $account->id)
                ->whereHas('entry', fn ($q) => $q->whereBetween('entry_date', $between))
                ->get()
                ->sortBy(fn ($l) => $l->entry->entry_date.'-'.$l->journal_entry_id)
                ->values();

            $running = 0;
            foreach ($lines as $line) {
                $running += $account->isDebitNormal() ? $line->debit - $line->credit : $line->credit - $line->debit;
                $line->balance = $running;
            }
        }

        return view('accounting.ledger', [
            'fy' => $fy,
            'accounts' => Account::orderBy('code')->get(),
            'account' => $account,
            'lines' => $lines,
        ]);
    }

    public function incomeStatement()
    {
        [$fy, $between] = $this->fyWindow();

        $income = $this->balancesByType('income', $between);
        $expense = $this->balancesByType('expense', $between);

        return view('accounting.income-statement', [
            'fy' => $fy,
            'income' => $income,
            'expense' => $expense,
            'totalIncome' => $income['accounts']->sum('amount'),
            'totalExpense' => $expense['accounts']->sum('amount'),
        ]);
    }

    public function balanceSheet()
    {
        [, $between] = $this->fyWindow();

        $assets = $this->balancesByType('asset', $between, true);
        $liabilities = $this->balancesByType('liability', $between, true);
        $equity = $this->balancesByType('equity', $between, true);

        $netResult = $this->netResult($between);

        return view('accounting.balance-sheet', [
            'fy' => FinancialYear::current(),
            'asOf' => $between[1],
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'netResult' => $netResult,
            'totals' => [
                'assets' => $assets['accounts']->sum('amount'),
                'liabilities' => $liabilities['accounts']->sum('amount') + max(0, -$netResult),
                'equity' => $equity['accounts']->sum('amount') + $netResult,
            ],
        ]);
    }

    private function fyWindow(): array
    {
        $fy = FinancialYear::current();

        return $fy
            ? [$fy, [$fy->start_date->toDateString(), $fy->end_date->toDateString()]]
            : [null, ['1900-01-01', now()->addCentury()->toDateString()]];
    }

    public function offerings(Request $request)
    {
        return $this->documentPage($request, 'receipt', 'accounting.offerings', 'accounting/offerings');
    }

    public function storeOffering(Request $request)
    {
        return $this->storeDocument($request, 'receipt');
    }

    public function payments(Request $request)
    {
        return $this->documentPage($request, 'payment', 'accounting.payments', 'accounting/payments');
    }

    public function storePayment(Request $request)
    {
        return $this->storeDocument($request, 'payment');
    }

    private function documentPage(Request $request, string $type, string $routeName, string $basePath)
    {
        [$fy, $between] = $this->fyWindow();

        $docs = ReceiptPayment::with(['categoryAccount', 'moneyAccount'])
            ->where('type', $type)
            ->when($fy, fn ($q) => $q->whereBetween('pay_date', $between))
            ->orderByDesc('pay_date')->paginate(10);

        return view($routeName === 'accounting.offerings' ? 'accounting.offerings' : 'accounting.payments', [
            'fy' => $fy,
            'docs' => $docs,
            'categoryAccounts' => Account::where('is_active', true)
                ->whereIn('type', [$type === 'receipt' ? 'income' : 'expense'])
                ->orderBy('code')->get(),
            'moneyAccounts' => Account::where('is_cash', true)->orderBy('code')->get(),
            'basePath' => $basePath,
        ]);
    }

    private function nextDocNo(string $type): string
    {
        $prefix = $type === 'receipt' ? 'RCP-' : 'PMT-';
        $max = ReceiptPayment::where('type', $type)->pluck('doc_no')
            ->map(fn ($n) => (int) substr($n, -4))->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function storeDocument(Request $request, string $type)
    {
        $data = $request->validate([
            'pay_date' => 'required|date',
            'party' => 'required|string|max:255',
            'category_account_id' => 'required|exists:accounts,id',
            'money_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,mobile',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $entry = DB::transaction(function () use ($data, $type) {
            $entry = JournalEntry::create([
                'entry_no' => JournalEntry::nextEntryNo(),
                'entry_date' => $data['pay_date'],
                'description' => ($type === 'receipt' ? 'Receipt from ' : 'Payment to ').$data['party'],
                'reference' => $data['reference'] ?? null,
                'status' => 'posted',
                'created_by' => auth()->user()?->name ?? 'Daniel Mwinuka',
            ]);

            if ($type === 'receipt') {
                JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $data['money_account_id'], 'debit' => $data['amount'], 'credit' => 0]);
                JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $data['category_account_id'], 'debit' => 0, 'credit' => $data['amount']]);
            } else {
                JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $data['category_account_id'], 'debit' => $data['amount'], 'credit' => 0]);
                JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $data['money_account_id'], 'debit' => 0, 'credit' => $data['amount']]);
            }

            $doc = ReceiptPayment::create($data + [
                'type' => $type,
                'doc_no' => $this->nextDocNo($type),
                'journal_entry_id' => $entry->id,
                'created_by' => auth()->user()?->name ?? 'Daniel Mwinuka',
            ]);

            return $doc;
        });

        AuditLog::record(($type === 'receipt' ? 'Recorded receipt' : 'Recorded payment'), 'Financial Accounting',
            "{$entry->doc_no} — {$entry->party} TZS ".number_format((float) $entry->amount, 2));

        return redirect()->back()->with('success',
            ($type === 'receipt' ? 'Receipt' : 'Payment').' '.$entry->doc_no.' of TZS '.number_format((float) $entry->amount).' recorded successfully.');
    }

    public function destroyDocument(ReceiptPayment $doc)
    {
        AuditLog::record('Deleted '.$doc->type.' document', 'Financial Accounting',
            "{$doc->doc_no} — {$doc->party} TZS ".number_format((float) $doc->amount));
        $entry = JournalEntry::find($doc->journal_entry_id);
        $doc->delete();
        if ($entry) {
            $entry->delete();
        }

        return back()->with('success', 'Document deleted successfully.');
    }

    public function cashBank(Request $request)
    {
        [$fy, $between] = $this->fyWindow();

        $cashAccounts = Account::where('is_cash', true)->orderBy('code')->get();
        $balances = [];
        foreach ($cashAccounts as $a) {
            $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
            $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');
            $balances[] = [
                'account' => $a,
                'debit' => round($d, 2),
                'credit' => round($c, 2),
                'balance' => round($d - $c, 2),
            ];
        }
        $balances = collect($balances);

        // Period inflows / outflows across all cash accounts
        $inflows = (float) $this->baseLineQuery($between)->clone()
            ->whereIn('account_id', $cashAccounts->pluck('id'))->sum('debit');
        $outflows = (float) $this->baseLineQuery($between)->clone()
            ->whereIn('account_id', $cashAccounts->pluck('id'))->sum('credit');
        $inflows = round($inflows, 2);
        $outflows = round($outflows, 2);

        // Month-by-month cash flow series for the current period window
        $monthly = [];
        $lineQuery = $this->baseLineQuery($between)->clone()
            ->whereIn('account_id', $cashAccounts->pluck('id'))
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->selectRaw("DATE_FORMAT(journal_entries.entry_date,'%Y-%m') ym, SUM(journal_lines.debit) din, SUM(journal_lines.credit) cout")
            ->groupBy('ym')->orderBy('ym')->get();
        foreach ($lineQuery as $row) {
            $monthly[] = [
                'month' => $row->ym,
                'label' => \Carbon\Carbon::createFromFormat('Y-m', $row->ym)->format('M Y'),
                'in' => round((float) $row->din, 2),
                'out' => round((float) $row->cout, 2),
                'net' => round((float) $row->din - (float) $row->cout, 2),
            ];
        }

        // Movements: optional per-account filter (account_uuid or account code)
        $accountFilter = $request->query('account');
        $movements = JournalLine::with(['entry', 'account'])
            ->whereHas('account', fn ($q) => $q->where('is_cash', true))
            ->when($accountFilter, fn ($q) => $q->whereHas('account', fn ($a) => $a->where('code', $accountFilter)->orWhere('id', $accountFilter)))
            ->when($fy, fn ($q) => $q->whereHas('entry', fn ($e) => $e->whereBetween('entry_date', $between)))
            ->latest('id')->take(40)->get();

        return view('accounting.cash-bank', [
            'fy' => $fy,
            'balances' => $balances,
            'totalCash' => $balances->sum('balance'),
            'inflows' => $inflows,
            'outflows' => $outflows,
            'netMovement' => round($inflows - $outflows, 2),
            'monthly' => $monthly,
            'movements' => $movements,
            'activeAccount' => $accountFilter,
        ]);
    }

    public function budgets(Request $request)
    {
        [$fy, $between] = $this->fyWindow();

        $eventId = $request->query('event_id');

        $budgets = Budget::with(['account', 'fy', 'event'])
            ->when($fy, fn ($q) => $q->where('fy_id', $fy?->id))
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->orderBy('id')->get();

        $budgets->each(function ($b) use ($between) {
            $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $b->account_id)->sum('debit');
            $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $b->account_id)->sum('credit');
            $b->actual = round($d - $c, 2);
        });

        $incomeTotal = (float) $this->baseLineQuery($between)->clone()->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')->where('accounts.type', 'income')->selectRaw('COALESCE(SUM(journal_lines.credit - journal_lines.debit),0) t')->value('t');

        return view('accounting.budgets', [
            'fy' => $fy,
            'budgets' => $budgets,
            'expenseAccounts' => Account::where('type', 'expense')->where('is_active', true)->orderBy('code')->get(),
            'allYears' => FinancialYear::orderByDesc('start_date')->get(),
            'allEvents' => \App\Models\Event::orderByDesc('start_date')->get(),
            'incomeTotal' => $incomeTotal,
        ]);
    }

    public function storeBudget(Request $request)
    {
        $data = $request->validate([
            'fy_id' => 'required|exists:financial_years,id',
            'account_id' => 'required|exists:accounts,id',
            'event_id' => 'nullable|exists:events,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $budget = Budget::updateOrCreate(
            ['fy_id' => $data['fy_id'], 'account_id' => $data['account_id'], 'event_id' => $data['event_id'] ?? null],
            ['amount' => $data['amount']]
        );

        AuditLog::record('Saved budget line', 'Financial Accounting — Budgets',
            $budget->account->name.' = TZS '.number_format((float) $budget->amount).($budget->event ? " ({$budget->event->title})" : ''));

        return back()->with('success', "Budget for {$budget->account->name} saved successfully.");
    }

    public function destroyBudget(Budget $budget)
    {
        AuditLog::record('Deleted budget line', 'Financial Accounting — Budgets', $budget->account->name);
        $budget->delete();

        return back()->with('success', 'Budget line removed successfully.');
    }

    public function reconciliation(Request $request)
    {
        $bankAccounts = Account::where('is_cash', true)->orderBy('code')->get();
        $accountId = $request->query('account');
        [$fy, $between] = $this->fyWindow();
        $asOf = $request->query('as_of', now()->toDateString());

        $selected = $accountId ? Account::findOrFail($accountId) : null;
        $ledgerBalance = 0;

        if ($selected) {
            $ledgerBalance = (float) JournalLine::where('account_id', $selected->id)
                ->whereHas('entry', fn ($q) => $q->where('status', 'posted')->whereDate('entry_date', '<=', $asOf))
                ->selectRaw('COALESCE(SUM(debit - credit),0) t')->value('t');
        }

        return view('accounting.reconciliation', [
            'fy' => $fy,
            'bankAccounts' => $bankAccounts,
            'selected' => $selected,
            'asOf' => $asOf,
            'ledgerBalance' => round($ledgerBalance, 2),
            'reconciliations' => BankReconciliation::with('account')->latest()->take(8)->get(),
        ]);
    }

    public function storeReconciliation(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|exists:accounts,id,is_cash,1',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $ledger = (float) JournalLine::where('account_id', $data['account_id'])
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted')->whereDate('entry_date', '<=', $data['statement_date']))
            ->selectRaw('COALESCE(SUM(debit - credit),0) t')->value('t');

        $rec = BankReconciliation::create([
            'account_id' => $data['account_id'],
            'statement_date' => $data['statement_date'],
            'statement_balance' => $data['statement_balance'],
            'ledger_balance' => round($ledger, 2),
            'difference' => round((float) $data['statement_balance'] - $ledger, 2),
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->user()?->name ?? 'Daniel Mwinuka',
        ]);

        AuditLog::record('Saved bank reconciliation', 'Financial Accounting — Reconciliation',
            $rec->account->code." as of {$rec->statement_date}, diff TZS ".number_format((float) $rec->difference, 2));

        $status = abs((float) $rec->difference) < 0.01 ? 'Account reconciled successfully.' : "Reconciliation saved with difference of TZS ".number_format(abs((float) $rec->difference), 2).'.';

        return back()->with(abs((float) $rec->difference) < 0.01 ? 'success' : 'info', $status);
    }

    public function transactions(Request $request)
    {
        [$fy, $between] = $this->fyWindow();
        $q = trim((string) $request->query('q'));
        $accountId = $request->query('account');

        $lines = JournalLine::with(['entry', 'account'])
            ->whereHas('entry', fn ($e) => $e
                ->where('status', 'posted')
                ->when($fy, fn ($ee) => $ee->whereBetween('entry_date', $between))
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                    ->where('description', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ->orWhere('entry_no', 'like', "%{$q}%"))))
            ->when($accountId, fn ($qq) => $qq->where('account_id', $accountId))
            ->latest('journal_lines.id')
            ->paginate(15);

        // Resolve the source record backing each journal entry so the list
        // also surfaces registration / pledge / manual payment records.
        $entries = $lines->pluck('entry')->unique('id');
        $attendeePays = \App\Models\EventAttendee::with('event')
            ->whereIn('journal_entry_id', $entries->pluck('id'))->get()
            ->keyBy('journal_entry_id');
        $pledgePays = \App\Models\PledgePayment::with(['pledge.event'])
            ->whereIn('journal_entry_id', $entries->pluck('id'))->get()
            ->keyBy('journal_entry_id');
        $receiptPays = \App\Models\ReceiptPayment::with('categoryAccount')
            ->whereIn('journal_entry_id', $entries->pluck('id'))->get()
            ->keyBy('journal_entry_id');

        $sources = collect();
        $entries->each(function ($entry) use (&$sources, $attendeePays, $pledgePays, $receiptPays) {
            $type = null;
            $label = null;
            $amount = null;

            if (isset($attendeePays[$entry->id])) {
                $a = $attendeePays[$entry->id];
                $type = 'Registration payment';
                $label = ($a->name ?? 'Attendee').($a->event ? ' — '.$a->event->title : '');
                $amount = $a->amount_paid;
            } elseif (isset($pledgePays[$entry->id])) {
                $p = $pledgePays[$entry->id];
                $type = 'Pledge payment';
                $label = $p->pledge?->name ?? $p->reference ?? 'Pledge';
                $amount = $p->amount;
                if ($p->pledge) {
                    $label .= ' ('.$p->pledge->pledge_no.')';
                }
            } elseif (isset($receiptPays[$entry->id])) {
                $r = $receiptPays[$entry->id];
                $type = $r->type === 'receipt' ? 'Contribution/Income' : ($r->type === 'payment' ? 'Expense' : ucfirst($r->type));
                $label = $r->party ?? $r->description ?? '';
                $amount = $r->amount;
            }

            $sources[$entry->id] = compact('type', 'label', 'amount');
        });

        return view('accounting.transactions', [
            'fy' => $fy,
            'lines' => $lines,
            'sources' => $sources,
            'accounts' => Account::orderBy('code')->get(),
            'q' => $q,
            'accountId' => $accountId,
        ]);
    }

    public function receiptPdf(JournalEntry $entry)
    {
        abort_unless($entry->status === 'posted', 404);

        $entry->loadMissing('lines.account');

        $lines = $entry->lines->map(function (JournalLine $l) {
            return [
                'code' => $l->account?->code ?? '—',
                'account' => $l->account?->name ?? '—',
                'description' => $l->description ?: '—',
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ];
        });

        $amount = max(
            (float) $entry->lines->sum('debit'),
            (float) $entry->lines->sum('credit')
        );

        // Reconstruct a single "money in" transaction (dr cash/asset, cr income/liability)
        $moneyIn = $entry->lines->every(function (JournalLine $l) {
            return $l->debit == 0 || $l->credit == 0;
        });

        $moneyInLines = $moneyIn
            ? $entry->lines->map(function (JournalLine $l) use ($amount) {
                $label = ($l->debit > 0 ? 'Received into ' : 'Received as ') . ($l->account?->name ?? 'account');
                return ['label' => $label, 'amount' => $l->debit > 0 ? $l->debit : $l->credit];
            })
            : collect();

        $reference = $entry->reference ?: 'JE-'.$entry->entry_date->format('Ymd');
        $receiptNo = str_replace('JE-', 'RCP-', (string) $entry->entry_no);

        $org = Setting::get('org.name', 'Open Gate Camp Mission');

        $qrData = 'OGCM|RCP|'.$receiptNo.'|'.number_format($amount, 2);
        $qr = app(\App\Services\QrCodeService::class)->pngDataUri($qrData, 3);

        $html = view('accounting.receipt', [
            'entry' => $entry,
            'lines' => $lines,
            'amount' => $amount,
            'moneyIn' => $moneyIn,
            'moneyInLines' => $moneyInLines,
            'receiptNo' => $receiptNo,
            'reference' => $reference,
            'org' => $org,
            'qr' => $qr,
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 350],         // 80mm wide, generous height so content is never clipped
            'margin_left'  => 4,
            'margin_right' => 4,
            'margin_top'   => 3,
            'margin_bottom' => 3,
            'tempDir' => storage_path('app/private/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'Receipt-'.preg_replace('/[^A-Za-z0-9-_]/', '', str_replace('JE-', '', $entry->entry_no)).'.pdf';

        return $mpdf->Output($filename, 'D');
    }

    private function baseLineQuery(array $between)
    {
        return JournalLine::whereHas('entry', fn ($q) => $q
            ->where('status', 'posted')
            ->whereBetween('entry_date', $between));
    }

    private function trialBalanceRows(array $between)
    {
        return Account::query()->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($between) {
                $debit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('debit');
                $credit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('credit');
                $net = round($debit - $credit, 2);

                return [
                    'account' => $account,
                    'debit' => $account->isDebitNormal() ? max(0, $net) : max(0, -$net),
                    'credit' => $account->isDebitNormal() ? max(0, -$net) : max(0, $net),
                    'activity' => $debit != 0 || $credit != 0,
                ];
            })
            ->filter(fn ($r) => $r['activity'])
            ->values();
    }

    private function balancesByType(string $type, array $between, bool $normalSideOnly = false): array
    {
        $accounts = Account::where('type', $type)->orderBy('code')->get();

        $accounts = $accounts
            ->map(function (Account $account) use ($between, $normalSideOnly) {
                $debit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('debit');
                $credit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('credit');
                $net = round($debit - $credit, 2);

                return [
                    'account' => $account,
                    'amount' => abs($net),
                    'rawNet' => $net,
                    'hasActivity' => $debit != 0 || $credit != 0,
                ];
            });

        return [
            'accounts' => $accounts->filter(fn ($r) => $normalSideOnly ? $r['hasActivity'] : $r['hasActivity'])->values(),
            'total' => $accounts->sum('rawNet'),
        ];
    }

    private function netResult(array $between): float
    {
        $incomeTotal = (float) $this->baseLineQuery($between)->clone()
            ->whereIn('account_id', Account::where('type', 'income')->pluck('id'))
            ->selectRaw('COALESCE(SUM(credit - debit),0) as t')->value('t');
        $expenseTotal = (float) $this->baseLineQuery($between)->clone()
            ->whereIn('account_id', Account::where('type', 'expense')->pluck('id'))
            ->selectRaw('COALESCE(SUM(debit - credit),0) as t')->value('t');

        return round($incomeTotal - $expenseTotal, 2);
    }

    private function periodTotals(array $between): array
    {
        $income = $this->balancesByType('income', $between)['total'];
        $expense = $this->balancesByType('expense', $between)['total'];
        $cash = Account::whereIn('code', ['1000', '1010', '1020'])->get()
            ->sum(function (Account $a) use ($between) {
                $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
                $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');

                return $d - $c;
            });

        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'result' => round($income - $expense, 2),
            'cash' => round((float) $cash, 2),
        ];
    }
}
