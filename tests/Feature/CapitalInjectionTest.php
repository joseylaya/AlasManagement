<?php

namespace Tests\Feature;

use App\Actions\Finance\CreateCapitalInjectionAction;
use App\Actions\Finance\ReverseCapitalInjectionAction;
use App\Models\CashTransaction;
use App\Models\FinancialAccount;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapitalInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_capital_injection_increases_cash_without_changing_profit_and_can_be_reversed(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $account = FinancialAccount::create(['name' => 'Test GCash', 'type' => 'ewallet', 'is_active' => true]);

        $capital = CreateCapitalInjectionAction::execute([
            'client_uuid' => '2f4e057f-0fd4-4ddf-9b50-311004d2ee80',
            'amount' => '10000.00', 'financial_account_id' => $account->id,
            'contribution_date' => now()->toDateString(), 'funding_source' => 'Owner Personal Bank Account',
        ], $owner);

        $this->assertSame('posted', $capital->status);
        $this->assertStringStartsWith('CAP-'.now()->format('Y').'-', $capital->capital_injection_number);
        $this->assertEquals(10000.0, FinanceService::getCurrentCash());
        $this->assertEquals(0.0, FinanceService::getMonthlyProfit());
        $this->assertDatabaseHas('cash_transactions', ['id' => $capital->cash_transaction_id, 'type' => 'capital_injection', 'amount' => 10000]);

        ReverseCapitalInjectionAction::execute($capital, 'Entry was recorded in error.', $owner, true);
        $this->assertSame('reversed', $capital->fresh()->status);
        $this->assertEquals(0.0, (float) CashTransaction::sum('amount'));
    }
}
