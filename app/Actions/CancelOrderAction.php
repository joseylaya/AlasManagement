<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\InventoryService;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public static function execute(Order $order, string $reason, ?User $user = null): Order
    {
        if ($order->order_status === 'cancelled') {
            return $order;
        }

        if ($order->order_status === 'completed') {
            throw new Exception("Completed order {$order->order_number} cannot be cancelled directly. Use a refund instead.");
        }

        $actor = $user ?? Auth::user();
        if (! $actor || ! $actor->canCancelOrder()) {
            throw new Exception('You do not have permission to cancel orders.');
        }
        $userId = $actor->id;

        return DB::transaction(function () use ($order, $reason, $userId) {
            $order->update([
                'order_status' => 'cancelled',
                'notes' => ($order->notes ? $order->notes . " | " : "") . "Cancelled: " . $reason,
                'updated_by' => $userId,
            ]);

            // Restore reserved stock to inventory
            foreach ($order->items as $item) {
                if ($item->product) {
                    InventoryService::addStock(
                        $item->product,
                        $item->quantity,
                        "Restored stock from cancelled order {$order->order_number}. Reason: {$reason}",
                        User::find($userId),
                        $order->order_number,
                        'return'
                    );
                }
            }

            ActivityLogService::log(
                'Order Cancelled',
                "Cancelled order {$order->order_number}. Reason: {$reason}. Restored items to stock.",
                $order
            );

            return $order;
        });
    }
}
