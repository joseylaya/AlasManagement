<?php

namespace App\Livewire\Orders;

use App\Actions\CancelOrderAction;
use App\Actions\CompleteOrderAction;
use App\Actions\UpdateOrderStatusAction;
use App\Models\Order;
use Exception;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedStatus = '';
    public string $selectedDelivery = '';

    public function updateStatus(int $orderId, string $newStatus): void
    {
        try {
            $order = Order::findOrFail($orderId);
            UpdateOrderStatusAction::execute($order, $newStatus);
            session()->flash('success', "Order {$order->order_number} status updated to {$newStatus}.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelOrder(int $orderId): void
    {
        try {
            $order = Order::findOrFail($orderId);
            CancelOrderAction::execute($order, "Cancelled by user from orders dashboard");
            session()->flash('success', "Order {$order->order_number} was cancelled and reserved stock restored.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = Order::with(['items', 'user']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->selectedStatus)) {
            $query->where('order_status', $this->selectedStatus);
        }

        if (!empty($this->selectedDelivery)) {
            $query->where('delivery_method', $this->selectedDelivery);
        }

        $orders = $query->latest('id')->paginate(10);

        return view('livewire.orders.index', [
            'orders' => $orders,
        ])->layout('layouts.app', ['pageHeader' => 'Orders & Fulfillment']);
    }
}
