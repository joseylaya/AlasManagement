<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogService;
use Exception;

class RejectOrderAction
{
    public static function execute(Order $order, User $rejector, string $reason): Order
    {
        // Guard: Only manager/owner can reject
        if (! $rejector->canApproveOrders()) {
            throw new Exception('You do not have permission to reject orders.');
        }

        // Guard: Must be pending approval
        if (! $order->isPendingApproval()) {
            throw new Exception("Order {$order->order_number} is not pending approval.");
        }

        if (empty(trim($reason))) {
            throw new Exception('A rejection reason is required.');
        }

        $order->update([
            'approval_status'  => 'rejected',
            'rejection_reason' => $reason,
            'approved_by'      => $rejector->id,
            'approved_at'      => now(),
            'updated_by'       => $rejector->id,
        ]);

        // Activity log
        ActivityLogService::log(
            'Order Rejected',
            "{$rejector->name} ({$rejector->role}) rejected Order {$order->order_number}. Reason: {$reason}",
            $order,
            ['approval_status' => 'rejected', 'reason' => $reason],
            $rejector
        );

        return $order->fresh();
    }
}
