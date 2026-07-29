<?php

namespace App\Actions;

use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompleteOrderAction
{
    public static function execute(Order $order, ?User $user = null): Order
    {
        if ($order->order_status === 'completed') {
            return $order;
        }

        if ($order->order_status === 'cancelled') {
            throw new Exception("Cancelled order {$order->order_number} cannot be marked as completed.");
        }

        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'order_status'  => 'completed',
                'payment_status' => 'paid',
                'updated_by'    => $userId,
            ]);

            // Create Cash Transaction if not already created for this order
            $existingTx = CashTransaction::where('order_id', $order->id)->first();
            if (!$existingTx) {
                // FIX: Create first, then update number from real auto-increment ID
                $cashTx = CashTransaction::create([
                    'transaction_number' => 'PENDING',
                    'user_id'            => $userId,
                    'type'               => 'sale',
                    'amount'             => $order->total_amount,
                    'order_id'           => $order->id,
                    'description'        => "Payment received for completed order {$order->order_number}",
                    'transaction_date'   => Carbon::now(),
                    'created_by'         => $userId,
                    'updated_by'         => $userId,
                ]);
                $cashTx->update(['transaction_number' => 'CTX-' . str_pad($cashTx->id, 6, '0', STR_PAD_LEFT)]);
            }

            ActivityLogService::log(
                'Order Completed',
                "Order {$order->order_number} marked as completed. Cash transaction recorded: ₱" . number_format($order->total_amount, 2),
                $order
            );

            return $order->fresh();
        });
    }
}
