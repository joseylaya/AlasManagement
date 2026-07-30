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
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search           = '';
    public string $selectedStatus   = '';
    public string $selectedDelivery = '';
    public string $selectedApproval = '';

    // Rejection modal state
    public bool   $showRejectModal  = false;
    public int    $rejectOrderId    = 0;
    public string $rejectReason     = '';

    // ─── Status Transitions ───────────────────────────────────────

    public function updateStatus(int $orderId, string $newStatus): void
    {
        $user = auth()->user();

        if (! $user->canManageOrderFulfillment()) {
            session()->flash('error', 'You do not have permission to change order status.');
            return;
        }

        try {
            $order = Order::findOrFail($orderId);

            if ($order->isPendingApproval()) {
                session()->flash('error', "Order {$order->order_number} cannot be processed — it is still pending approval.");
                return;
            }

            UpdateOrderStatusAction::execute($order, $newStatus);
            session()->flash('success', "Order {$order->order_number} status updated to {$newStatus}.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelOrder(int $orderId): void
    {
        if (! auth()->user()->canCancelOrder()) {
            session()->flash('error', 'You do not have permission to cancel orders.');
            return;
        }

        try {
            $order = Order::findOrFail($orderId);
            CancelOrderAction::execute($order, 'Cancelled from orders list');
            session()->flash('success', "Order {$order->order_number} cancelled and stock restored.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ─── Approval Actions ─────────────────────────────────────────

    public function approveOrder(int $orderId): void
    {
        try {
            $order = Order::findOrFail($orderId);
            ApproveOrderAction::execute($order, auth()->user());
            session()->flash('success', "Order {$order->order_number} approved successfully.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRejectModal(int $orderId): void
    {
        $this->rejectOrderId  = $orderId;
        $this->rejectReason   = '';
        $this->showRejectModal = true;
    }

    public function confirmReject(): void
    {
        if (empty(trim($this->rejectReason))) {
            session()->flash('error', 'Please enter a reason for rejection.');
            return;
        }

        try {
            $order = Order::findOrFail($this->rejectOrderId);
            RejectOrderAction::execute($order, auth()->user(), $this->rejectReason);
            session()->flash('success', "Order {$order->order_number} rejected.");
            $this->showRejectModal = false;
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectReason    = '';
    }

    // ─── Render ───────────────────────────────────────────────────

    public function render()
    {
        $user  = auth()->user();
        $query = Order::with(['items', 'creator', 'approver']);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%');
            });
        }

        if (! empty($this->selectedStatus)) {
            $query->where('order_status', $this->selectedStatus);
        }

        if (! empty($this->selectedDelivery)) {
            $query->where('delivery_method', $this->selectedDelivery);
        }

        if (! empty($this->selectedApproval)) {
            $query->where('approval_status', $this->selectedApproval);
        }

        // Staff only see their own orders
        if ($user->isStaff()) {
            $query->where('created_by', $user->id);
        }

        $orders = $query->latest('id')->paginate(10);

        // Pending approval count for Manager/Owner badge
        $pendingApprovalCount = $user->canApproveOrders()
            ? Order::where('approval_status', 'pending_approval')->count()
            : 0;

        return view('livewire.orders.index', [
            'orders'               => $orders,
            'pendingApprovalCount' => $pendingApprovalCount,
            'canApprove'           => $user->canApproveOrders(),
            'canManageFulfillment' => $user->canManageOrderFulfillment(),
            'canCancel'            => $user->canCancelOrder(),
            'isStaff'              => $user->isStaff(),
        ])->layout('layouts.app', ['pageHeader' => 'Orders & Fulfillment']);
    }
}
