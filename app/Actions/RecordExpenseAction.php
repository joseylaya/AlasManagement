<?php

namespace App\Actions;

use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordExpenseAction
{
    public static function execute(array $data, ?User $user = null): Expense
    {
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new Exception("Expense amount must be greater than zero.");
        }

        $actor = $user ?? Auth::user();
        if (! $actor || ! $actor->canModifyFinance()) {
            throw new Exception('You do not have permission to record expenses.');
        }
        $userId = $actor->id;

        return DB::transaction(function () use ($data, $amount, $userId) {
            // FIX: Create expense first, use real auto-increment ID for expense number
            $expense = Expense::create([
                'expense_number'      => 'PENDING',
                'expense_category_id' => $data['expense_category_id'],
                'user_id'             => $userId,
                'amount'              => $amount,
                'expense_date'        => $data['expense_date'] ?? Carbon::today(),
                'description'         => $data['description'],
                'receipt_url'         => $data['receipt_url'] ?? null,
                'status'              => 'completed',
                'created_by'          => $userId,
                'updated_by'          => $userId,
            ]);

            $expenseNumber = 'EXP-' . str_pad($expense->id, 6, '0', STR_PAD_LEFT);
            $expense->update(['expense_number' => $expenseNumber]);

            // Create Cash Transaction for Expense (negative amount)
            $cashTx = CashTransaction::create([
                'transaction_number' => 'PENDING',
                'user_id'            => $userId,
                'type'               => 'expense',
                'direction'          => 'cash_out',
                'amount'             => -$amount,
                'expense_id'         => $expense->id,
                'description'        => "Expense {$expenseNumber}: {$expense->description}",
                'transaction_date'   => Carbon::now(),
                'sync_source'        => 'online',
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);
            $cashTx->update(['transaction_number' => 'CTX-' . str_pad($cashTx->id, 6, '0', STR_PAD_LEFT)]);

            ActivityLogService::log(
                'Expense Recorded',
                "Recorded expense {$expenseNumber} of ₱" . number_format($amount, 2) . " for {$expense->description}",
                $expense
            );

            return $expense;
        });
    }
}
