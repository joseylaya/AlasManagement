<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\InventoryService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateOrderAction
{
    public static function execute(array $orderData, array $items, ?User $user = null): Order
    {
        if (empty($items)) {
            throw new Exception("An order must contain at least one product item.");
        }

        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($orderData, $items, $userId) {
            $totalAmount = 0.00;
            $orderItemsData = [];

            // Validate items and build snapshot data BEFORE creating the order
            foreach ($items as $item) {
                $product = Product::with('inventory')->findOrFail($item['product_id']);

                if ($product->status !== 'active') {
                    throw new Exception("Product {$product->product_name} is inactive/archived and cannot be sold.");
                }

                $quantity = (int) $item['quantity'];
                if ($quantity <= 0) {
                    throw new Exception("Quantity for {$product->product_name} must be greater than zero.");
                }

                // FIX: Remove stale accessor check. InventoryService::deductStock() is
                // the authoritative check — it uses lockForUpdate() to prevent race conditions.

                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->selling_price;
                $subtotal  = $unitPrice * $quantity;
                $totalAmount += $subtotal;

                $orderItemsData[] = [
                    'product'      => $product,
                    'product_name' => $product->product_name,
                    'sku'          => $product->sku,
                    'unit_price'   => $unitPrice,
                    'quantity'     => $quantity,
                    'subtotal'     => $subtotal,
                ];
            }

            // FIX: Create order first, then use auto-increment ID for order_number
            $order = Order::create([
                'order_number'   => 'PENDING', // Temporary, will be updated below
                'user_id'        => $userId,
                'customer_name'  => $orderData['customer_name'] ?? 'Walk-in Customer',
                'customer_phone' => $orderData['customer_phone'] ?? null,
                'customer_email' => $orderData['customer_email'] ?? null,
                'delivery_method'  => $orderData['delivery_method'] ?? 'shipping',
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'meetup_date'      => $orderData['meetup_date'] ?? null,
                'meetup_location'  => $orderData['meetup_location'] ?? null,
                'order_status'   => $orderData['order_status'] ?? 'pending',
                'payment_status' => $orderData['payment_status'] ?? 'pending',
                'payment_method' => $orderData['payment_method'] ?? 'cash',
                'total_amount'   => $totalAmount,
                'notes'          => $orderData['notes'] ?? null,
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);

            // FIX: Generate guaranteed-unique order number from real auto-increment ID
            $orderNumber = 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
            $order->update(['order_number' => $orderNumber]);

            foreach ($orderItemsData as $itemData) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $itemData['product']->id,
                    'product_name' => $itemData['product_name'],
                    'sku'          => $itemData['sku'],
                    'unit_price'   => $itemData['unit_price'],
                    'quantity'     => $itemData['quantity'],
                    'subtotal'     => $itemData['subtotal'],
                ]);

                // Authoritative stock deduction with DB lock inside InventoryService
                InventoryService::deductStock(
                    $itemData['product'],
                    $itemData['quantity'],
                    "Reserved for order {$orderNumber}",
                    User::find($userId),
                    $orderNumber,
                    'sale'
                );
            }

            ActivityLogService::log(
                'Order Created',
                "Created order {$orderNumber} for {$order->customer_name} totaling ₱" . number_format($totalAmount, 2),
                $order->fresh()
            );

            // FIX: Let CompleteOrderAction handle cash recording when status transitions.
            // Do NOT call it inside CreateOrderAction. If order is already completed on
            // creation, call it AFTER the transaction completes.
            if ($order->order_status === 'completed' || $order->payment_status === 'paid') {
                CompleteOrderAction::execute($order->fresh(), User::find($userId));
            }

            return $order->fresh();
        });
    }
}
