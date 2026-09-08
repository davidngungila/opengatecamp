<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ReceiptPayment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingExportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        Role::updateOrCreate(['name' => 'Admin'], ['permissions' => ['*']]);
        $user = User::factory()->create(['role_id' => Role::where('name', 'Admin')->value('id')]);
        $this->actingAs($user);
    }

    private function seedAccounts(): Account
    {
        $income = Account::create(['code' => '4000', 'name' => 'Tithes', 'type' => 'income', 'is_active' => true]);
        Account::create(['code' => '5000', 'name' => 'Catering', 'type' => 'expense', 'is_active' => true]);
        Account::create(['code' => '1000', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true, 'is_cash' => true]);

        return $income;
    }

    private function postEntry(Account $income): JournalEntry
    {
        $cash = Account::where('code', '1000')->first();

        $entry = JournalEntry::create([
            'entry_no' => JournalEntry::nextEntryNo(),
            'entry_date' => now()->toDateString(),
            'description' => 'Receipt from Test Donor',
            'status' => 'posted',
            'created_by' => 'Test',
        ]);

        JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $cash->id, 'debit' => 50000, 'credit' => 0]);
        JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $income->id, 'debit' => 0, 'credit' => 50000]);

        return $entry;
    }

    private function assertPdf(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_income_statement_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.income-statement.export')));
    }

    public function test_trial_balance_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.trial-balance.export')));
    }

    public function test_balance_sheet_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.balance-sheet.export')));
    }

    public function test_offerings_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.offerings.export')));
    }

    public function test_payments_export_returns_pdf(): void
    {
        $this->admin();
        $this->seedAccounts();
        $cash = Account::where('code', '1000')->first();
        $expense = Account::where('code', '5000')->first();

        $this->post(route('accounting.payments.store'), [
            'pay_date' => now()->toDateString(),
            'party' => 'Food Supplier Ltd',
            'category_account_id' => $expense->id,
            'money_account_id' => $cash->id,
            'amount' => 25000,
            'method' => 'bank',
        ]);

        $this->assertPdf($this->get(route('accounting.payments.export')));
    }

    public function test_cash_bank_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.cash-bank.export')));
    }

    public function test_budgets_export_returns_pdf(): void
    {
        $this->admin();
        $fy = FinancialYear::create([
            'name' => 'FY 2026',
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'is_default' => true,
        ]);
        $account = Account::create(['code' => '5009', 'name' => 'Transport', 'type' => 'expense', 'is_active' => true]);
        Budget::create(['fy_id' => $fy->id, 'account_id' => $account->id, 'amount' => 300000]);

        $this->assertPdf($this->get(route('accounting.budgets.export')));
    }

    public function test_accounts_export_returns_pdf(): void
    {
        $this->admin();
        $this->seedAccounts();

        $this->assertPdf($this->get(route('accounting.accounts.export')));
    }

    public function test_journal_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.journal.export')));
    }

    public function test_transactions_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.transactions.export')));
    }

    public function test_ledger_export_returns_pdf(): void
    {
        $this->admin();
        $income = $this->seedAccounts();
        $this->postEntry($income);

        $this->assertPdf($this->get(route('accounting.ledger.export', ['account' => $income->ref()])));
    }

    public function test_ledger_url_uses_encrypted_account_ref(): void
    {
        $this->admin();
        $income = $this->seedAccounts();

        $ref = $income->ref();
        $this->assertNotSame((string) $income->id, $ref);
        $this->assertSame($income->id, Account::resolveRef($ref)?->id);
        $this->assertSame($income->id, Account::resolveRef((string) $income->id)?->id);
        $this->assertNull(Account::resolveRef('not-a-real-ref'));

        $response = $this->get(route('accounting.ledger', ['account' => $ref]));
        $response->assertOk();
        $response->assertSee($income->name);
    }

    public function test_ledger_rejects_unknown_ref_with_404(): void
    {
        $this->admin();
        $this->seedAccounts();

        $this->get(route('accounting.ledger', ['account' => 'garbage-ref']))->assertNotFound();
    }

    public function test_receipt_without_party_defaults_to_other_income(): void
    {
        $this->admin();
        $cash = Account::create(['code' => '1000', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true, 'is_cash' => true]);
        $cat = Account::create(['code' => '4040', 'name' => 'Other Income', 'type' => 'income', 'is_active' => true]);

        $response = $this->post(route('accounting.offerings.store'), [
            'pay_date' => now()->toDateString(),
            'category_account_id' => $cat->id,
            'money_account_id' => $cash->id,
            'amount' => 10000,
            'method' => 'cash',
        ]);

        $response->assertSessionHas('success');

        $doc = ReceiptPayment::first();
        $this->assertNotNull($doc);
        $this->assertSame('Other Income', $doc->party);
    }
}