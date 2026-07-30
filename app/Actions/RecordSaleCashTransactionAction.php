<?php

namespace App\Actions;

use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class RecordSaleCashTransactionAction
{
    /** Record payment exactly once, independent from operational completion. */
    public static function execute(Order $order, User $user): ?CashTransaction
    {
        if ($order->payment_status !== 'paid' || ! $order->isApproved()) {
            return null;
        }

        $existing = CashTransaction::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $transaction = CashTransaction::create([
            'transaction_number' => 'PENDING',
            'client_uuid' => $order->client_uuid,
            'user_id' => $user->id,
            'type' => 'sale',
            'direction' => 'cash_in',
            'amount' => $order->total_amount,
            'order_id' => $order->id,
            'description' => "Payment received for order {$order->order_number}",
            'transaction_date' => Carbon::now(),
            'sync_source' => $order->sync_source ?? 'online',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $transaction->update(['transaction_number' => 'CTX-' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT)]);

        return $transaction;
    }
}
