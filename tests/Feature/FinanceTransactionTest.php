<?php

namespace Tests\Feature;

use App\Actions\RecordExpenseAction;
use App\Actions\RecordOwnerWithdrawalAction;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_expense_creates_negative_cash_transaction(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $cat = ExpenseCategory::create(['name' => 'Supplies']);

        $expense = RecordExpenseAction::execute([
            'expense_category_id' => $cat->id,
            'amount' => 500.00,
            'description' => 'Polymailers batch',
        ], $user);

        $this->assertDatabaseHas('cash_transactions', [
            'expense_id' => $expense->id,
            'type' => 'expense',
            'amount' => -500.00,
        ]);
    }

    public function test_owner_withdrawal_decreases_cash_without_affecting_operating_profit(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $drawal = RecordOwnerWithdrawalAction::execute([
            'amount' => 1000.00,
            'reason' => 'Owner drawdown',
        ], $owner);

        $this->assertDatabaseHas('cash_transactions', [
            'owner_drawal_id' => $drawal->id,
            'type' => 'owner_withdrawal',
            'amount' => -1000.00,
        ]);

        $this->assertEquals(-1000.00, FinanceService::getCurrentCash());
        // Operating profit should be 0.00 since owner withdrawals do not reduce operating profit!
        $this->assertEquals(0.00, FinanceService::getMonthlyProfit());
    }
}
