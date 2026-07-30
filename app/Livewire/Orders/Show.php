<?php

namespace App\Livewire\Orders;

use App\Actions\ApproveOrderAction;
use App\Actions\CancelOrderAction;
use App\Actions\CompleteOrderAction;
use App\Actions\RejectOrderAction;
use App\Actions\UpdateOrderStatusAction;
use App\Models\Order;
use Exception;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    // Rejection modal
    public bool   $showRejectModal = false;
    public string $rejectReason    = '';

    public function mount(int $id): void
    {
        $this->order = Order::with([
            'items.product',
            'creator',
            'updater',
            'approver',
            'cashTransactions',
        ])->findOrFail($id);
    }

    // ─── Approval Actions ─────────────────────────────────────────

    public function approveOrder(): void
    {
        try {
            ApproveOrderAction::execute($this->order, auth()->user());
            $this->order->refresh();
            session()->flash('success', "Order {$this->order->order_number} approved.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRejectModal(): void
    {
        $this->rejectReason    = '';
        $this->showRejectModal = true;
    }

    public function confirmReject(): void
    {
        if (empty(trim($this->rejectReason))) {
            session()->flash('error', 'Please enter a reason for rejection.');
            return;
        }

        try {
            RejectOrderAction::execute($this->order, auth()->user(), $this->rejectReason);
            $this->order->refresh();
            $this->showRejectModal = false;
            session()->flash('success', "Order {$this->order->order_number} rejected.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectReason    = '';
    }

    // ─── Status Transitions ───────────────────────────────────────

    public function updateStatus(string $newStatus): void
    {
        $user = auth()->user();

        if (! $user->canManageOrderFulfillment()) {
            session()->flash('error', 'You do not have permission to change order status.');
            return;
        }

        if ($this->order->isPendingApproval()) {
            session()->flash('error', 'This order cannot be processed until it is approved.');
            return;
        }

        try {
            UpdateOrderStatusAction::execute($this->order, $newStatus);
            $this->order->refresh();
            session()->flash('success', "Status updated to {$newStatus}.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelOrder(): void
    {
        if (! auth()->user()->canCancelOrder()) {
            session()->flash('error', 'You do not have permission to cancel this order.');
            return;
        }

        try {
            CancelOrderAction::execute($this->order, 'Cancelled from order detail page');
            $this->order->refresh();
            session()->flash('success', "Order cancelled and stock restored.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ─── Render ───────────────────────────────────────────────────

    public function render()
    {
        $user = auth()->user();

        return view('livewire.orders.show', [
            'canApprove'           => $this->order->canBeApprovedBy($user),
            'canReject'            => $this->order->canBeApprovedBy($user),
            'canChangeStatus'      => $this->order->canTransitionStatus($user),
            'canCancel'            => $user->canCancelOrder() && ! in_array($this->order->order_status, ['completed', 'cancelled']),
            'isStaff'              => $user->isStaff(),
        ])->layout('layouts.app', ['pageHeader' => 'Order Detail']);
    }
}
