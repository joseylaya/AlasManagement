<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogService;
use Exception;
use Illuminate\Support\Facades\Auth;

class ApproveOrderAction
{
    public static function execute(Order $order, User $approver): Order
    {
        // Guard: Only manager/owner can approve
        if (! $approver->canApproveOrders()) {
            throw new Exception('You do not have permission to approve orders.');
        }

        // Guard: Must be pending approval
        if (! $order->isPendingApproval()) {
            throw new Exception("Order {$order->order_number} is not pending approval (current: {$order->approvalStatusLabel()}).");
        }

        $previousStatus = $order->approval_status;

        $order->update([
            'approval_status' => 'approved',
            'approved_by'     => $approver->id,
            'approved_at'     => now(),
            'updated_by'      => $approver->id,
        ]);

        RecordSaleCashTransactionAction::execute($order->fresh(), $approver);

        // Activity log
        ActivityLogService::log(
            'Order Approved',
            "{$approver->name} ({$approver->role}) approved Order {$order->order_number}. Status changed from {$previousStatus} to approved.",
            $order,
            ['previous_approval_status' => $previousStatus, 'approval_status' => 'approved'],
            $approver
        );

        return $order->fresh();
    }
}
