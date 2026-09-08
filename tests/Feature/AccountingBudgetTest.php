<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\FinancialYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingBudgetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        Role::updateOrCreate(['name' => 'Admin'], ['permissions' => ['*']]);
        $user = User::factory()->create(['role_id' => Role::where('name', 'Admin')->value('id')]);
        $this->actingAs($user);
    }

    public function test_budget_api_returns_account_and_fy_ids(): void
    {
        $this->admin();

        $fy = FinancialYear::create([
            'name' => 'FY 2026',
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'is_default' => true,
        ]);
        $account = Account::create([
            'code' => '5001',
            'name' => 'Catering',
            'type' => 'expense',
            'is_active' => true,
        ]);
        $budget = Budget::create([
            'fy_id' => $fy->id,
            'account_id' => $account->id,
            'amount' => 1000000,
        ]);

        $response = $this->get(route('accounting.api.budget', $budget));

        $response->assertOk();
        $json = $response->json();

        $this->assertSame($account->id, $json['account']['id']);
        $this->assertSame('Catering', $json['account']['name']);
        $this->assertSame($fy->id, $json['fy_id']);
        $this->assertArrayHasKey('event_id', $json);
        $this->assertEquals(1000000, $json['amount']);
    }

    public function test_budget_listing_and_ledger_link_use_real_account_id(): void
    {
        $this->admin();

        $fy = FinancialYear::create([
            'name' => 'FY 2026',
            'start_date' => now()->subYear(),
            'end_date' => now()->addYear(),
            'is_default' => true,
        ]);
        $account = Account::create([
            'code' => '5001',
            'name' => 'Catering',
            'type' => 'expense',
            'is_active' => true,
        ]);
        Budget::create([
            'fy_id' => $fy->id,
            'account_id' => $account->id,
            'amount' => 1000000,
        ]);

        $page = $this->get(route('accounting.budgets'));
        $page->assertOk();
        $page->assertSee('Catering');

        $link = route('accounting.ledger', ['account' => $account->id]);
        $this->assertStringNotContainsString('undefined', $link);

        $ledger = $this->get($link);
        $ledger->assertOk();
        $ledger->assertSee('Catering');
    }
}