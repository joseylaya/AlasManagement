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

        $actor = $user ?? Auth::user();
        if (! $actor || ! $order->canTransitionStatus($actor)) {
            throw new Exception('You do not have permission to complete this order.');
        }
        $userId = $actor->id;

        return DB::transaction(function () use ($order, $userId) {
            $order->update([
                'order_status'  => 'completed',
                'payment_status' => 'paid',
                'updated_by'    => $userId,
            ]);

            RecordSaleCashTransactionAction::execute($order->fresh(), User::findOrFail($userId));

            ActivityLogService::log(
                'Order Completed',
                "Order {$order->order_number} marked as completed. Cash transaction recorded: ₱" . number_format($order->total_amount, 2),
                $order
            );

            return $order->fresh();
        });
    }
}
