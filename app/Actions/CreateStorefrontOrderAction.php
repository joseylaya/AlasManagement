<?php

namespace App\Actions;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ActivityLogService;
use App\Services\Shipping\ShippingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateStorefrontOrderAction
{
    public static function execute(array $data, string $idempotencyKey, string $commerceMode = 'live'): Order
    {
        if ($existing = Order::where('checkout_idempotency_key', $idempotencyKey)->first()) {
            return $existing->load('items');
        }

        return DB::transaction(function () use ($data, $idempotencyKey, $commerceMode) {
            if ($existing = Order::where('checkout_idempotency_key', $idempotencyKey)->first()) {
                return $existing->load('items');
            }

            $requested = collect($data['items'])->keyBy('variant_id')->sortKeys();
            $products = Product::query()
                ->whereIn('id', $requested->keys())
                ->where('status', 'active')
                ->with('storefrontProduct')
                ->get()->keyBy('id');

            if ($products->count() !== $requested->count()) {
                throw ValidationException::withMessages(['items' => ['One or more variants are unavailable.']]);
            }

            $inventories = Inventory::query()
                ->whereIn('product_id', $requested->keys())
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()->keyBy('product_id');

            $total = '0.00';
            foreach ($requested as $productId => $line) {
                $inventory = $inventories->get($productId);
                if (! $inventory || $inventory->current_stock < $line['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for variant {$productId}."],
                    ]);
                }
                $total = bcadd($total, bcmul((string) $products[$productId]->selling_price, (string) $line['quantity'], 2), 2);
            }

            $shippingQuote = null;
            if ($data['delivery_method'] === 'shipping') {
                $shippingQuote = app(ShippingService::class)->validateQuote($data['shipping_quote_id'], $data['shipping_session_id'], $data['delivery_address'], $data['items']);
            }
            $subtotal = $total;
            $shippingAmount = $shippingQuote?->amount ?? '0.00';
            $total = bcadd($subtotal, (string) $shippingAmount, 2);

            $order = Order::create([
                'order_number' => 'PENDING-'.Str::uuid(),
                'public_token' => (string) Str::uuid(),
                'checkout_idempotency_key' => $idempotencyKey,
                'customer_name' => $data['customer']['name'],
                'customer_email' => $data['customer']['email'],
                'customer_phone' => $data['customer']['phone'],
                'delivery_method' => $data['delivery_method'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'delivery_address_snapshot' => $data['delivery_address'] ?? null,
                'delivery_provider' => $shippingQuote?->provider,
                'delivery_service' => $shippingQuote?->service_name,
                'shipping_quote_id' => $shippingQuote?->id,
                'shipping_quote_source' => $shippingQuote?->source,
                'shipping_status' => 'pending',
                'meetup_location' => $data['meetup_location'] ?? null,
                'order_status' => 'pending',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'payment_status' => 'pending',
                'payment_method' => 'online',
                'subtotal_amount' => $subtotal,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $total,
                'currency' => 'PHP',
                'commerce_mode' => $commerceMode,
                'sync_source' => $commerceMode === 'sandbox' ? 'storefront_sandbox' : 'storefront',
                'server_updated_at' => now(),
            ]);
            $orderNumber = ($commerceMode === 'sandbox' ? 'TEST-WEB-' : 'WEB-').str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
            $order->update(['order_number' => $orderNumber]);

            foreach ($requested as $productId => $line) {
                $product = $products[$productId];
                $quantity = (int) $line['quantity'];
                $subtotal = bcmul((string) $product->selling_price, (string) $quantity, 2);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->storefrontProduct?->name ?? $product->product_name,
                    'sku' => $product->sku,
                    'unit_price' => $product->selling_price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);
                if ($commerceMode !== 'sandbox') {
                    $inventories[$productId]->decrement('current_stock', $quantity);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'movement_type' => 'sale',
                        'quantity' => -$quantity,
                        'reason' => "Reserved for storefront order {$orderNumber}",
                        'reference_number' => $orderNumber,
                    ]);
                }
            }

            ActivityLogService::log('Storefront Order Created', "Created {$orderNumber} from storefront checkout.", $order, [
                'total_amount' => $total,
                'currency' => 'PHP',
                'commerce_mode' => $commerceMode,
            ]);

            return $order->fresh('items');
        }, 3);
    }
}
