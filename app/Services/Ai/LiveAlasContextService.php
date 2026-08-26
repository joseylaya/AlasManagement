<?php

namespace App\Services\Ai;

use App\Models\Order;
use App\Models\Product;
use App\Models\SupportConversation;

class LiveAlasContextService
{
    public function forMessage(SupportConversation $conversation, string $message): array
    {
        $facts = [];
        $productId = data_get($conversation->context, 'variant_id') ?: data_get($conversation->context, 'product_id');
        if ($productId) {
            $product = Product::query()->with('inventory')->where('status', 'active')->find($productId);
            if ($product) $facts[] = $this->productFact($product);
        } elseif (data_get($conversation->context, 'product_slug')) {
            $products = Product::query()->with('inventory')->where('status', 'active')->whereHas('storefrontProduct', fn ($query) => $query->where('slug', data_get($conversation->context, 'product_slug')))->limit(20)->get();
            foreach ($products as $product) $facts[] = $this->productFact($product);
        } elseif (preg_match('/(?:price|stock|available|availability|size|medium|small|large|shirt|tee)/i', $message)) {
            $terms = collect(preg_split('/\s+/', $message))->filter(fn ($term) => mb_strlen($term) >= 3)->take(5);
            $products = Product::query()->with('inventory')->where('status', 'active')->where(function ($query) use ($terms) {
                foreach ($terms as $term) $query->orWhere('product_name', 'like', '%'.$term.'%')->orWhere('color', 'like', '%'.$term.'%')->orWhere('size', 'like', '%'.$term.'%');
            })->limit(5)->get();
            foreach ($products as $product) $facts[] = $this->productFact($product);
        }

        if ($conversation->customer->user_id && preg_match('/order|delivery|shipp/i', $message)) {
            Order::query()->where('user_id', $conversation->customer->user_id)->latest()->limit(3)->get()->each(function ($order) use (&$facts) {
                $facts[] = ['type' => 'ORDER', 'id' => (string) $order->id, 'text' => "Authorized order {$order->order_number}: order status {$order->order_status}; payment status {$order->payment_status}; shipping status ".($order->shipping_status ?: 'not available').'.'];
            });
        }
        return $facts;
    }

    private function productFact(Product $product): array
    {
        return ['type' => 'PRODUCT', 'id' => (string) $product->id, 'text' => sprintf('Live product data: %s; SKU %s; color %s; size %s; current price PHP %s; current available quantity %d.', $product->product_name, $product->sku, $product->color ?: 'not specified', $product->size ?: 'not specified', $product->selling_price, $product->inventory?->current_stock ?? 0)];
    }
}
