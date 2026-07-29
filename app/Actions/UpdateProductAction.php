<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class UpdateProductAction
{
    public static function execute(Product $product, array $data, ?User $user = null): Product
    {
        $userId = $user ? $user->id : Auth::id();

        $product->update([
            'product_name' => $data['product_name'] ?? $product->product_name,
            'sku' => isset($data['sku']) ? strtoupper(trim($data['sku'])) : $product->sku,
            'category' => $data['category'] ?? $product->category,
            'color' => $data['color'] ?? $product->color,
            'size' => $data['size'] ?? $product->size,
            'description' => $data['description'] ?? $product->description,
            'selling_price' => $data['selling_price'] ?? $product->selling_price,
            'cost_price' => $data['cost_price'] ?? $product->cost_price,
            'image_url' => $data['image_url'] ?? $product->image_url,
            'status' => $data['status'] ?? $product->status,
            'updated_by' => $userId,
        ]);

        if (isset($data['min_stock_threshold']) && $product->inventory) {
            $product->inventory->update(['min_stock_threshold' => (int) $data['min_stock_threshold'], 'updated_by' => $userId]);
        }

        ActivityLogService::log(
            'Product Updated',
            "Updated product details for {$product->product_name} ({$product->sku}).",
            $product
        );

        return $product;
    }
}
