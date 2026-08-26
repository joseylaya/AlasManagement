<?php

namespace App\Livewire\Orders;

use App\Actions\CreateOrderAction;
use App\Models\Product;
use Exception;
use Livewire\Component;

class Create extends Component
{
    public string $customer_name = 'Walk-in Customer';
    public string $customer_phone = '';
    public string $customer_email = '';
    public string $delivery_method = 'shipping';
    public string $shipping_address = '';
    public ?string $meetup_date = null;
    public string $meetup_location = '';
    public string $payment_method = 'cash';
    public string $payment_status = 'pending';
    public string $notes = '';

    // Order status is only editable by Manager/Owner
    public string $order_status = 'pending';

    // Order Line Items Array
    public array $cartItems = [];
    public ?int $selectedProductId = null;
    public int $selectedQuantity = 1;

    public function mount(): void
    {
        $firstProduct = Product::where('status', 'active')->first();
        if ($firstProduct) {
            $this->selectedProductId = $firstProduct->id;
        }
    }

    public function addItem(): void
    {
        if (!$this->selectedProductId) {
            return;
        }

        $product = Product::with('inventory')->find($this->selectedProductId);
        if (!$product) {
            return;
        }

        if ($product->current_stock < $this->selectedQuantity) {
            session()->flash('error', "Insufficient stock for {$product->product_name}. Available: {$product->current_stock}.");
            return;
        }

        // Check if already in cart
        foreach ($this->cartItems as $index => $item) {
            if ($item['product_id'] === $product->id) {
                $newQty = $item['quantity'] + $this->selectedQuantity;
                if ($product->current_stock < $newQty) {
                    session()->flash('error', "Cannot add more. Max stock available for {$product->product_name} is {$product->current_stock}.");
                    return;
                }
                $this->cartItems[$index]['quantity'] = $newQty;
                $this->cartItems[$index]['subtotal'] = $newQty * $item['unit_price'];
                $this->selectedQuantity = 1;
                return;
            }
        }

        $this->cartItems[] = [
            'product_id' => $product->id,
            'name' => $product->product_name,
            'sku' => $product->sku,
            'unit_price' => (float) $product->selling_price,
            'quantity' => $this->selectedQuantity,
            'subtotal' => (float) ($product->selling_price * $this->selectedQuantity),
        ];

        $this->selectedQuantity = 1;
    }

    public function removeItem(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
    }

    public function getGrandTotalProperty(): float
    {
        return array_reduce($this->cartItems, fn($acc, $item) => $acc + $item['subtotal'], 0.00);
    }

    public function saveOrder(): void
    {
        $this->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'delivery_method' => 'required|in:shipping,meetup',
            'shipping_address' => 'required_if:delivery_method,shipping|nullable|string|max:1000',
            'meetup_location' => 'required_if:delivery_method,meetup|nullable|string|max:255',
            'meetup_date' => 'required_if:delivery_method,meetup|nullable|date',
            'payment_method' => 'required|string|max:50',
            'payment_status' => 'required|in:paid,pending',
            'order_status' => 'required|in:pending,confirmed,packed,completed',
            'cartItems' => 'required|array|min:1',
        ]);

        try {
            $orderData = [
                'customer_name' => $this->customer_name,
                'customer_phone' => $this->customer_phone,
                'customer_email' => $this->customer_email,
                'delivery_method' => $this->delivery_method,
                'shipping_address' => $this->shipping_address,
                'meetup_date' => $this->meetup_date,
                'meetup_location' => $this->meetup_location,
                'payment_method' => $this->payment_method,
                'payment_status' => $this->payment_status,
                'order_status' => $this->order_status,
                'notes' => $this->notes,
            ];

            $order = CreateOrderAction::execute($orderData, $this->cartItems);

            session()->flash('success', "Order {$order->order_number} created successfully for ₱" . number_format($order->total_amount, 2));
            $this->redirect(route('orders.index'), navigate: true);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $products = Product::with('inventory')->where('status', 'active')->get();

        return view('livewire.orders.create', [
            'products' => $products,
            'isStaff'  => auth()->user()->isStaff(),
            'canSetStatus' => auth()->user()->canManageOrderFulfillment(),
        ])->layout('layouts.app', ['pageHeader' => 'Create New Order']);
    }
}
