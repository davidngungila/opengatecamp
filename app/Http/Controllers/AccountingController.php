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
use App\Services\ReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $ref = $request->query('account');
        $account = $ref ? (Account::resolveRef($ref) ?? abort(404)) : null;

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

    public function offeringDetail(ReceiptPayment $doc)
    {
        $doc->load(['categoryAccount', 'moneyAccount', 'journalEntry.lines.account']);
        return response()->json($doc);
    }

    public function paymentDetail(ReceiptPayment $doc)
    {
        $doc->load(['categoryAccount', 'moneyAccount', 'journalEntry.lines.account']);
        return response()->json($doc);
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
            'party' => $type === 'receipt' ? 'nullable|string|max:255' : 'required|string|max:255',
            'category_account_id' => 'required|exists:accounts,id',
            'money_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,mobile',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        if ($type === 'receipt') {
            $data['party'] = trim((string) ($data['party'] ?? '')) ?: 'Other Income';
        }

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

        $lines = JournalLine::with(['entry.lines.account', 'account'])
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

    public function receiptPdf(Request $request, JournalEntry $entry)
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

        $org = Setting::get('org.name', 'OpenGate Camp Connect');

        // Resolve who paid (registering attendee / pledge / manual receipt party).
        $payer = $this->resolvePayer($entry);

        $payload = 'OGCM|RCP|'.$receiptNo.'|'.number_format($amount, 2);
        $qrData = route('verify', ['code' => $payload], true);
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
            'payer' => $payer,
            'qr' => $qr,
            'logoPath' => public_path('logo.png'),
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 350],
            'margin_left'  => 4,
            'margin_right' => 4,
            'margin_top'   => 3,
            'margin_bottom' => 3,
            'tempDir' => storage_path('app/private/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'Receipt-'.preg_replace('/[^A-Za-z0-9-_]/', '', str_replace('JE-', '', $entry->entry_no)).'.pdf';

        return $mpdf->Output($filename, $request->boolean('inline') ? 'I' : 'D');
    }

    private function pdfResponse(\Mpdf\Mpdf $mpdf, string $filename)
    {
        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function brandedReport(string $title, string $subtitle, array $columns, array $rows, array $totals = [], array $filters = [])
    {
        return app(ReportPdfService::class)->generate(
            ['title' => $title, 'subtitle' => $subtitle, 'filters' => $filters],
            $columns,
            $rows,
            $totals
        );
    }

    public function exportAccountsPdf(Request $request)
    {
        $accounts = Account::withSum('journalLines as total_debit', 'debit')
            ->withSum('journalLines as total_credit', 'credit')
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->orderBy('code')->get();

        $rows = $accounts->map(fn ($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'type' => ucfirst($a->type),
            'debit' => 'TZS '.number_format((float) $a->total_debit, 2),
            'credit' => 'TZS '.number_format((float) $a->total_credit, 2),
        ])->all();

        $totals = [
            ['label' => 'Accounts', 'value' => number_format($accounts->count())],
            ['label' => 'Total Debits', 'value' => 'TZS '.number_format($accounts->sum('total_debit'), 2)],
            ['label' => 'Total Credits', 'value' => 'TZS '.number_format($accounts->sum('total_credit'), 2)],
        ];

        $filters = $request->query('type') ? ['Type' => ucfirst($request->query('type'))] : [];

        $mpdf = $this->brandedReport('Chart of Accounts', 'Account listing and balances', [
            ['label' => 'Code', 'key' => 'code'],
            ['label' => 'Account Name', 'key' => 'name'],
            ['label' => 'Type', 'key' => 'type'],
            ['label' => 'Total Debits', 'key' => 'debit', 'align' => 'right'],
            ['label' => 'Total Credits', 'key' => 'credit', 'align' => 'right'],
        ], $rows, $totals, $filters);

        return $this->pdfResponse($mpdf, 'Chart-of-Accounts-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportJournalPdf()
    {
        [$fy, $between] = $this->fyWindow();

        $entries = JournalEntry::with(['lines.account'])
            ->when($fy, fn ($q) => $q->whereBetween('entry_date', $between))
            ->orderByDesc('entry_date')->orderByDesc('id')->get();

        $rows = $entries->map(fn ($e) => [
            'entry' => $e->entry_no,
            'date' => $e->entry_date->format('d M Y'),
            'description' => Str::limit($e->description ?? '—', 46),
            'reference' => $e->reference ?? '—',
            'lines' => $e->lines->count(),
            'amount' => 'TZS '.number_format((float) $e->lines->sum('debit'), 2),
            'status' => ucfirst($e->status),
        ])->all();

        $totals = [
            ['label' => 'Entries', 'value' => number_format($entries->count())],
            ['label' => 'Total Dr/Cr', 'value' => 'TZS '.number_format($entries->sum(fn ($e) => (float) $e->lines->sum('debit')), 2)],
        ];

        $mpdf = $this->brandedReport('Journal Entries', $fy ? 'Period: '.$fy->name : 'All periods', [
            ['label' => 'Entry No', 'key' => 'entry'],
            ['label' => 'Date', 'key' => 'date'],
            ['label' => 'Description', 'key' => 'description'],
            ['label' => 'Reference', 'key' => 'reference'],
            ['label' => 'Lines', 'key' => 'lines'],
            ['label' => 'Amount (TZS)', 'key' => 'amount', 'align' => 'right'],
            ['label' => 'Status', 'key' => 'status'],
        ], $rows, $totals);

        return $this->pdfResponse($mpdf, 'Journal-Entries-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportTrialBalancePdf()
    {
        [$fy, $between] = $this->fyWindow();

        $rows = $this->trialBalanceRows($between);

        $dataRows = $rows->map(fn ($r) => [
            'code' => $r['account']->code,
            'name' => $r['account']->name,
            'type' => ucfirst($r['account']->type),
            'debit' => $r['debit'] > 0 ? 'TZS '.number_format($r['debit'], 2) : '—',
            'credit' => $r['credit'] > 0 ? 'TZS '.number_format($r['credit'], 2) : '—',
        ])->all();

        $totals = [
            ['label' => 'Total Debits', 'value' => 'TZS '.number_format($rows->sum('debit'), 2)],
            ['label' => 'Total Credits', 'value' => 'TZS '.number_format($rows->sum('credit'), 2)],
        ];

        $mpdf = $this->brandedReport('Trial Balance', $fy ? 'Period: '.$fy->name : 'All periods', [
            ['label' => 'Code', 'key' => 'code'],
            ['label' => 'Account', 'key' => 'name'],
            ['label' => 'Type', 'key' => 'type'],
            ['label' => 'Debit (TZS)', 'key' => 'debit', 'align' => 'right'],
            ['label' => 'Credit (TZS)', 'key' => 'credit', 'align' => 'right'],
        ], $dataRows, $totals);

        return $this->pdfResponse($mpdf, 'Trial-Balance-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportLedgerPdf(Request $request)
    {
        [$fy, $between] = $this->fyWindow();
        $ref = $request->query('account');
        $account = $ref ? (Account::resolveRef($ref) ?? abort(404)) : null;

        $filestem = $account ? 'Ledger-'.preg_replace('/[^A-Za-z0-9-_]/', '', $account->code) : 'General-Ledger-All-Accounts';

        if (! $account) {
            $data = [];
            foreach (Account::orderBy('code')->get() as $a) {
                $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
                $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');
                if ($d != 0 || $c != 0) {
                    $data[] = [
                        'code' => $a->code,
                        'name' => $a->name,
                        'debit' => 'TZS '.number_format($d, 2),
                        'credit' => 'TZS '.number_format($c, 2),
                        'net' => 'TZS '.number_format($d - $c, 2),
                    ];
                }
            }

            $mpdf = $this->brandedReport('General Ledger — All Accounts', $fy ? 'Period: '.$fy->name : 'All periods', [
                ['label' => 'Code', 'key' => 'code'],
                ['label' => 'Account', 'key' => 'name'],
                ['label' => 'Debit (TZS)', 'key' => 'debit', 'align' => 'right'],
                ['label' => 'Credit (TZS)', 'key' => 'credit', 'align' => 'right'],
                ['label' => 'Net (TZS)', 'key' => 'net', 'align' => 'right'],
            ], $data, [
                ['label' => 'Accounts with activity', 'value' => number_format(count($data))],
            ]);

            return $this->pdfResponse($mpdf, $filestem.'-'.now()->format('Ymd-His').'.pdf');
        }

        $lines = JournalLine::with('entry')
            ->where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->whereBetween('entry_date', $between))
            ->get()
            ->sortBy(fn ($l) => $l->entry->entry_date.'-'.$l->journal_entry_id)
            ->values();

        $running = 0;
        $rows = [];
        foreach ($lines as $line) {
            $running += $account->isDebitNormal() ? $line->debit - $line->credit : $line->credit - $line->debit;
            $rows[] = [
                'date' => $line->entry->entry_date->format('d M Y'),
                'entry' => $line->entry->entry_no,
                'description' => $line->description ?: ($line->entry->description ?: '—'),
                'debit' => $line->debit > 0 ? 'TZS '.number_format($line->debit, 2) : '—',
                'credit' => $line->credit > 0 ? 'TZS '.number_format($line->credit, 2) : '—',
                'balance' => 'TZS '.number_format(abs($running), 2).' '.($account->isDebitNormal() ? 'Dr' : 'Cr'),
            ];
        }

        $mpdf = $this->brandedReport('General Ledger — '.$account->code, $account->name.' · '.($fy ? $fy->name : 'All periods'), [
            ['label' => 'Date', 'key' => 'date'],
            ['label' => 'Entry', 'key' => 'entry'],
            ['label' => 'Description', 'key' => 'description'],
            ['label' => 'Debit (TZS)', 'key' => 'debit', 'align' => 'right'],
            ['label' => 'Credit (TZS)', 'key' => 'credit', 'align' => 'right'],
            ['label' => 'Balance (TZS)', 'key' => 'balance', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Entries', 'value' => number_format(count($rows))],
            ['label' => 'Closing Balance', 'value' => 'TZS '.number_format(abs($running), 2)],
        ]);

        return $this->pdfResponse($mpdf, $filestem.'-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportIncomeStatementPdf()
    {
        [$fy, $between] = $this->fyWindow();

        $income = $this->balancesByType('income', $between);
        $expense = $this->balancesByType('expense', $between);

        $rows = $income['accounts']
            ->map(fn ($r) => ['section' => 'Income', 'code' => $r['account']->code, 'name' => $r['account']->name, 'amount' => 'TZS '.number_format($r['amount'], 2)])
            ->concat($expense['accounts']->map(fn ($r) => ['section' => 'Expense', 'code' => $r['account']->code, 'name' => $r['account']->name, 'amount' => 'TZS '.number_format($r['amount'], 2)]))
            ->values()->all();

        $totalIncome = $income['accounts']->sum('amount');
        $totalExpense = $expense['accounts']->sum('amount');

        $mpdf = $this->brandedReport('Income & Expenditure Statement', $fy ? 'For '.$fy->name : 'For all periods', [
            ['label' => 'Section', 'key' => 'section'],
            ['label' => 'Code', 'key' => 'code'],
            ['label' => 'Account', 'key' => 'name'],
            ['label' => 'Amount (TZS)', 'key' => 'amount', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Total Income', 'value' => 'TZS '.number_format($totalIncome, 2)],
            ['label' => 'Total Expenses', 'value' => 'TZS '.number_format($totalExpense, 2)],
            ['label' => 'Surplus / Deficit', 'value' => 'TZS '.number_format($totalIncome - $totalExpense, 2)],
        ]);

        return $this->pdfResponse($mpdf, 'Income-Statement-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportBalanceSheetPdf()
    {
        [, $between] = $this->fyWindow();

        $assets = $this->balancesByType('asset', $between, true);
        $liabilities = $this->balancesByType('liability', $between, true);
        $equity = $this->balancesByType('equity', $between, true);
        $netResult = $this->netResult($between);

        $rows = collect()
            ->concat($assets['accounts']->map(fn ($r) => ['section' => 'Assets', 'code' => $r['account']->code, 'name' => $r['account']->name, 'amount' => 'TZS '.number_format($r['amount'], 2)]))
            ->concat($liabilities['accounts']->map(fn ($r) => ['section' => 'Liabilities', 'code' => $r['account']->code, 'name' => $r['account']->name, 'amount' => 'TZS '.number_format($r['amount'], 2)]))
            ->concat($equity['accounts']->map(fn ($r) => ['section' => 'Equity', 'code' => $r['account']->code, 'name' => $r['account']->name, 'amount' => 'TZS '.number_format($r['amount'], 2)]))
            ->push(['section' => 'Equity', 'code' => '—', 'name' => 'Surplus / (Deficit) for period', 'amount' => 'TZS '.number_format($netResult, 2)])
            ->values()->all();

        $mpdf = $this->brandedReport('Balance Sheet', 'As of '.$between[1], [
            ['label' => 'Section', 'key' => 'section'],
            ['label' => 'Code', 'key' => 'code'],
            ['label' => 'Account', 'key' => 'name'],
            ['label' => 'Amount (TZS)', 'key' => 'amount', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Total Assets', 'value' => 'TZS '.number_format($assets['accounts']->sum('amount'), 2)],
            ['label' => 'Total Liabilities', 'value' => 'TZS '.number_format($liabilities['accounts']->sum('amount'), 2)],
            ['label' => 'Total Equity', 'value' => 'TZS '.number_format($equity['accounts']->sum('amount') + $netResult, 2)],
        ]);

        return $this->pdfResponse($mpdf, 'Balance-Sheet-'.now()->format('Ymd-His').'.pdf');
    }

    private function documentRows(string $type, array $between): array
    {
        $docs = ReceiptPayment::with(['categoryAccount', 'moneyAccount'])
            ->where('type', $type)
            ->whereBetween('pay_date', $between)
            ->orderByDesc('pay_date')->get();

        $rows = $docs->map(fn ($d) => [
            'doc' => $d->doc_no,
            'date' => $d->pay_date->format('d M Y'),
            'party' => $d->party,
            'category' => $d->categoryAccount?->code.' — '.($d->categoryAccount?->name ?? '—'),
            'method' => ucfirst($d->method),
            'amount' => 'TZS '.number_format((float) $d->amount, 2),
        ])->all();

        return [$docs, $rows];
    }

    public function exportOfferingsPdf()
    {
        [$fy, $between] = $this->fyWindow();

        [$docs, $rows] = $this->documentRows('receipt', $between);

        $mpdf = $this->brandedReport('Offerings, Contributions & Donations', $fy ? 'Period: '.$fy->name : 'All periods', [
            ['label' => 'Doc No', 'key' => 'doc'],
            ['label' => 'Date', 'key' => 'date'],
            ['label' => 'From / Source', 'key' => 'party'],
            ['label' => 'Category', 'key' => 'category'],
            ['label' => 'Method', 'key' => 'method'],
            ['label' => 'Amount (TZS)', 'key' => 'amount', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Receipts', 'value' => number_format(count($docs))],
            ['label' => 'Total Received', 'value' => 'TZS '.number_format($docs->sum('amount'), 2)],
        ]);

        return $this->pdfResponse($mpdf, 'Offering-Receipts-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportPaymentsPdf()
    {
        [$fy, $between] = $this->fyWindow();

        [$docs, $rows] = $this->documentRows('payment', $between);

        $mpdf = $this->brandedReport('Payments & Expenses', $fy ? 'Period: '.$fy->name : 'All periods', [
            ['label' => 'Voucher', 'key' => 'doc'],
            ['label' => 'Date', 'key' => 'date'],
            ['label' => 'Paid To', 'key' => 'party'],
            ['label' => 'Expense Account', 'key' => 'category'],
            ['label' => 'Method', 'key' => 'method'],
            ['label' => 'Amount (TZS)', 'key' => 'amount', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Payments', 'value' => number_format(count($docs))],
            ['label' => 'Total Paid', 'value' => 'TZS '.number_format($docs->sum('amount'), 2)],
        ]);

        return $this->pdfResponse($mpdf, 'Payment-Expenses-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportCashBankPdf()
    {
        [$fy, $between] = $this->fyWindow();

        $cashAccounts = Account::where('is_cash', true)->orderBy('code')->get();

        $balances = collect();
        foreach ($cashAccounts as $a) {
            $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
            $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');
            $balances->push([
                'account' => $a,
                'debit' => round($d, 2),
                'credit' => round($c, 2),
                'balance' => round($d - $c, 2),
            ]);
        }

        $rows = $balances->map(fn ($b) => [
            'code' => $b['account']->code,
            'name' => $b['account']->name,
            'in' => 'TZS '.number_format($b['debit'], 2),
            'out' => 'TZS '.number_format($b['credit'], 2),
            'balance' => 'TZS '.number_format($b['balance'], 2),
        ])->all();

        $mpdf = $this->brandedReport('Cash & Bank Management', $fy ? 'Period: '.$fy->name : 'All periods', [
            ['label' => 'Code', 'key' => 'code'],
            ['label' => 'Account', 'key' => 'name'],
            ['label' => 'Inflows (TZS)', 'key' => 'in', 'align' => 'right'],
            ['label' => 'Outflows (TZS)', 'key' => 'out', 'align' => 'right'],
            ['label' => 'Net Balance (TZS)', 'key' => 'balance', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Total Cash', 'value' => 'TZS '.number_format($balances->sum('balance'), 2)],
            ['label' => 'Total Inflows', 'value' => 'TZS '.number_format($balances->sum('debit'), 2)],
            ['label' => 'Total Outflows', 'value' => 'TZS '.number_format($balances->sum('credit'), 2)],
        ]);

        return $this->pdfResponse($mpdf, 'Cash-Bank-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportBudgetsPdf(Request $request)
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

        $rows = $budgets->map(fn ($b) => [
            'account' => $b->account->code.' — '.$b->account->name,
            'event' => $b->event?->title ?? 'General',
            'budget' => 'TZS '.number_format((float) $b->amount, 2),
            'actual' => 'TZS '.number_format((float) $b->actual, 2),
            'variance' => 'TZS '.number_format((float) $b->amount - (float) $b->actual, 2),
        ])->all();

        $filters = $eventId ? ['Event' => $budgets->first()?->event?->title ?? $eventId] : [];

        $mpdf = $this->brandedReport('Budget Management', $fy ? 'Period: '.$fy->name : 'Select a financial year', [
            ['label' => 'Account', 'key' => 'account'],
            ['label' => 'Event', 'key' => 'event'],
            ['label' => 'Budget (TZS)', 'key' => 'budget', 'align' => 'right'],
            ['label' => 'Actual (TZS)', 'key' => 'actual', 'align' => 'right'],
            ['label' => 'Variance (TZS)', 'key' => 'variance', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Budget Lines', 'value' => number_format(count($budgets))],
            ['label' => 'Total Budget', 'value' => 'TZS '.number_format($budgets->sum('amount'), 2)],
            ['label' => 'Total Actual', 'value' => 'TZS '.number_format($budgets->sum('actual'), 2)],
        ], $filters);

        return $this->pdfResponse($mpdf, 'Budgets-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportTransactionsPdf(Request $request)
    {
        [$fy, $between] = $this->fyWindow();
        $q = trim((string) $request->query('q'));
        $accountId = $request->query('account');

        $lines = JournalLine::with(['entry.lines.account', 'account'])
            ->whereHas('entry', fn ($e) => $e
                ->where('status', 'posted')
                ->when($fy, fn ($ee) => $ee->whereBetween('entry_date', $between))
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                    ->where('description', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ->orWhere('entry_no', 'like', "%{$q}%"))))
            ->when($accountId, fn ($qq) => $qq->where('account_id', $accountId))
            ->latest('journal_lines.id')
            ->get();

        $rows = $lines->map(fn ($l) => [
            'entry' => $l->entry->entry_no,
            'date' => $l->entry->entry_date->format('d M Y'),
            'account' => $l->account->code.' — '.$l->account->name,
            'description' => $l->description ?: ($l->entry->description ?: '—'),
            'debit' => $l->debit > 0 ? 'TZS '.number_format($l->debit, 2) : '—',
            'credit' => $l->credit > 0 ? 'TZS '.number_format($l->credit, 2) : '—',
        ])->all();

        $filters = [];
        if ($fy) {
            $filters['Period'] = $fy->name;
        }
        if ($q !== '') {
            $filters['Search'] = $q;
        }
        if ($accountId) {
            $filters['Account'] = $accountId;
        }

        $mpdf = $this->brandedReport('Transaction History', $fy ? 'Period: '.$fy->name : 'All periods', [
            ['label' => 'Entry No', 'key' => 'entry'],
            ['label' => 'Date', 'key' => 'date'],
            ['label' => 'Account', 'key' => 'account'],
            ['label' => 'Description', 'key' => 'description'],
            ['label' => 'Debit (TZS)', 'key' => 'debit', 'align' => 'right'],
            ['label' => 'Credit (TZS)', 'key' => 'credit', 'align' => 'right'],
        ], $rows, [
            ['label' => 'Transactions', 'value' => number_format(count($lines))],
            ['label' => 'Total Debits', 'value' => 'TZS '.number_format($lines->sum('debit'), 2)],
            ['label' => 'Total Credits', 'value' => 'TZS '.number_format($lines->sum('credit'), 2)],
        ], $filters);

        return $this->pdfResponse($mpdf, 'Transactions-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Determine who the money came from for a posted journal entry.
     * Prefers registrations, then pledges, then manual receipts; falls back to the
     * journal entry's own reference/description.
     */
    private function resolvePayer(JournalEntry $entry): string
    {
        $attendee = \App\Models\EventAttendee::where('journal_entry_id', $entry->id)->first();
        if ($attendee) {
            return trim($attendee->name) ?: ($attendee->event?->title ?? 'Attendee');
        }

        $pledgePay = \App\Models\PledgePayment::with('pledge')->where('journal_entry_id', $entry->id)->first();
        if ($pledgePay && $pledgePay->pledge) {
            return trim($pledgePay->pledge->name) ?: 'Pledge '.$pledgePay->pledge->pledge_no;
        }

        $receiptPay = \App\Models\ReceiptPayment::where('journal_entry_id', $entry->id)->first();
        if ($receiptPay) {
            return trim($receiptPay->party) ?: (trim($receiptPay->description) ?: 'Anonymous');
        }

        return trim((string) $entry->reference) ?: (trim((string) $entry->description) ?: 'Anonymous');
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

    public function apiOverview()
    {
        [$fy, $between] = $this->fyWindow();

        $incomeAccounts = Account::where('type', 'income')->orderBy('code')->get()
            ->map(function (Account $a) use ($between) {
                $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
                $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');
                $net = round(abs($c - $d), 2);
                return $net > 0 ? ['code' => $a->code, 'name' => $a->name, 'amount' => $net] : null;
            })->filter()->values();

        $expenseAccounts = Account::where('type', 'expense')->orderBy('code')->get()
            ->map(function (Account $a) use ($between) {
                $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
                $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');
                $net = round(abs($d - $c), 2);
                return $net > 0 ? ['code' => $a->code, 'name' => $a->name, 'amount' => $net] : null;
            })->filter()->values();

        $cashAccounts = Account::whereIn('code', ['1000', '1010', '1020'])->orderBy('code')->get()
            ->map(function (Account $a) use ($between) {
                $d = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('debit');
                $c = (float) $this->baseLineQuery($between)->clone()->where('account_id', $a->id)->sum('credit');
                return ['code' => $a->code, 'name' => $a->name, 'balance' => round($d - $c, 2)];
            })->filter(fn ($b) => $b['balance'] != 0)->values();

        return response()->json([
            'fy' => $fy ? $fy->name : 'All periods',
            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,
            'cashAccounts' => $cashAccounts,
            'totalIncome' => $incomeAccounts->sum('amount'),
            'totalExpense' => $expenseAccounts->sum('amount'),
        ]);
    }

    public function apiAccountDetail(Account $account)
    {
        [$fy, $between] = $this->fyWindow();

        $debit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('debit');
        $credit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('credit');

        $recentLines = JournalLine::with('entry')
            ->where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
            ->when($fy, fn ($q) => $q->whereHas('entry', fn ($e) => $e->whereBetween('entry_date', $between)))
            ->latest('id')->take(10)->get()
            ->map(fn ($l) => [
                'date' => $l->entry->entry_date->format('d M Y'),
                'entry_no' => $l->entry->entry_no,
                'description' => $l->description ?: $l->entry->description,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ]);

        return response()->json([
            'account' => ['id' => $account->id, 'ref' => $account->ref(), 'code' => $account->code, 'name' => $account->name, 'type' => $account->type],
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'net' => round($debit - $credit, 2),
            'recentLines' => $recentLines,
        ]);
    }

    public function apiJournalDetail(JournalEntry $entry)
    {
        $entry->load('lines.account');
        $doc = ReceiptPayment::where('journal_entry_id', $entry->id)->first();

        return response()->json([
            'entry' => [
                'entry_no' => $entry->entry_no,
                'entry_date' => $entry->entry_date->format('d M Y'),
                'description' => $entry->description,
                'reference' => $entry->reference,
                'status' => $entry->status,
                'created_by' => $entry->created_by,
                'created_at' => $entry->created_at?->format('d M Y H:i'),
            ],
            'lines' => $entry->lines->map(fn ($l) => [
                'code' => $l->account?->code,
                'account' => $l->account?->name,
                'description' => $l->description,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ]),
            'total_debit' => (float) $entry->lines->sum('debit'),
            'total_credit' => (float) $entry->lines->sum('credit'),
            'doc' => $doc ? [
                'doc_no' => $doc->doc_no,
                'type' => $doc->type,
                'party' => $doc->party,
                'amount' => (float) $doc->amount,
            ] : null,
        ]);
    }

    public function apiTrialBalanceDetail(Account $account)
    {
        [$fy, $between] = $this->fyWindow();

        $debit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('debit');
        $credit = (float) $this->baseLineQuery($between)->clone()->where('account_id', $account->id)->sum('credit');

        $recentLines = JournalLine::with('entry')
            ->where('account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
            ->when($fy, fn ($q) => $q->whereHas('entry', fn ($e) => $e->whereBetween('entry_date', $between)))
            ->latest('id')->take(10)->get()
            ->map(fn ($l) => [
                'date' => $l->entry->entry_date->format('d M Y'),
                'entry_no' => $l->entry->entry_no,
                'description' => $l->description ?: $l->entry->description,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ]);

        return response()->json([
            'account' => ['id' => $account->id, 'ref' => $account->ref(), 'code' => $account->code, 'name' => $account->name, 'type' => $account->type],
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'net' => round($debit - $credit, 2),
            'recentLines' => $recentLines,
        ]);
    }

    public function apiLedgerDetail(JournalLine $line)
    {
        $line->load(['entry.lines.account', 'account']);

        return response()->json([
            'entry_no' => $line->entry->entry_no,
            'entry_date' => $line->entry->entry_date->format('d M Y'),
            'description' => $line->description ?: $line->entry->description,
            'reference' => $line->entry->reference,
            'status' => $line->entry->status,
            'account' => ['code' => $line->account->code, 'name' => $line->account->name],
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'allLines' => $line->entry->lines->map(fn ($l) => [
                'code' => $l->account?->code,
                'account' => $l->account?->name,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ]),
        ]);
    }

    public function apiBudgetDetail(Budget $budget)
    {
        $budget->load(['account', 'event', 'fy']);
        [$fy, $between] = $this->fyWindow();

        $actual = (float) $this->baseLineQuery($between)->clone()->where('account_id', $budget->account_id)->sum('debit')
            - (float) $this->baseLineQuery($between)->clone()->where('account_id', $budget->account_id)->sum('credit');

        $recentLines = JournalLine::with('entry')
            ->where('account_id', $budget->account_id)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
            ->when($fy, fn ($q) => $q->whereHas('entry', fn ($e) => $e->whereBetween('entry_date', $between)))
            ->latest('id')->take(10)->get()
            ->map(fn ($l) => [
                'date' => $l->entry->entry_date->format('d M Y'),
                'entry_no' => $l->entry->entry_no,
                'description' => $l->description ?: $l->entry->description,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ]);

        return response()->json([
            'account' => ['id' => $budget->account_id, 'ref' => $budget->account->ref(), 'code' => $budget->account->code, 'name' => $budget->account->name],
            'account_id' => $budget->account_id,
            'event' => $budget->event ? $budget->event->title : null,
            'event_id' => $budget->event_id,
            'fy' => $budget->fy->name,
            'fy_id' => $budget->fy_id,
            'amount' => (float) $budget->amount,
            'actual' => round($actual, 2),
            'variance' => round((float) $budget->amount - $actual, 2),
            'recentLines' => $recentLines,
        ]);
    }

    public function apiCashMovementDetail(JournalLine $line)
    {
        $line->load(['entry.lines.account', 'account']);

        return response()->json([
            'entry_no' => $line->entry->entry_no,
            'entry_date' => $line->entry->entry_date->format('d M Y'),
            'description' => $line->entry->description,
            'reference' => $line->entry->reference,
            'account' => ['code' => $line->account->code, 'name' => $line->account->name],
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'allLines' => $line->entry->lines->map(fn ($l) => [
                'code' => $l->account?->code,
                'account' => $l->account?->name,
                'debit' => (float) $l->debit,
                'credit' => (float) $l->credit,
            ]),
        ]);
    }
}
