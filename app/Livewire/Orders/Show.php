<?php

namespace App\Livewire\Orders;

use App\Actions\CancelOrderAction;
use App\Actions\UpdateOrderStatusAction;
use App\Models\Order;
use Exception;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public function mount(int $id): void
    {
        $this->order = Order::with(['items.product', 'user', 'cashTransactions'])->findOrFail($id);
    }

    public function updateStatus(string $newStatus): void
    {
        try {
            $this->order = UpdateOrderStatusAction::execute($this->order, $newStatus);
            session()->flash('success', "Order {$this->order->order_number} status updated to {$newStatus}.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelOrder(): void
    {
        try {
            $this->order = CancelOrderAction::execute($this->order, "Cancelled by user");
            session()->flash('success', "Order {$this->order->order_number} was cancelled.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.orders.show')->layout('layouts.app', ['pageHeader' => 'Order Details — ' . $this->order->order_number]);
    }
}
