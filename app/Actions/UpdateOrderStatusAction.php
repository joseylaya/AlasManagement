<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class UpdateOrderStatusAction
{
    public static function execute(Order $order, string $newStatus, ?User $user = null): Order
    {
        $userId = $user ? $user->id : Auth::id();

        if ($newStatus === 'completed') {
            return CompleteOrderAction::execute($order, User::find($userId));
        }

        if ($newStatus === 'cancelled') {
            return CancelOrderAction::execute($order, "Status updated to cancelled", User::find($userId));
        }

        $oldStatus = $order->order_status;
        $order->update([
            'order_status' => $newStatus,
            'updated_by' => $userId,
        ]);

        ActivityLogService::log(
            'Order Status Updated',
            "Updated status of order {$order->order_number} from {$oldStatus} to {$newStatus}.",
            $order
        );

        return $order;
    }
}
