<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\StorefrontProduct;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public static function execute(Product $product, array $data, ?User $user = null): Product
    {
        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($product, $data, $user, $userId) {
            $storefrontProduct = ! empty($data['storefront_product_id'])
                ? StorefrontProduct::findOrFail($data['storefront_product_id'])
                : ($product->storefrontProduct ?? UpsertStorefrontProductAction::execute($data, null, $user));

            if (! empty($data['update_storefront_product'])) {
                $storefrontProduct = UpsertStorefrontProductAction::execute($data, $storefrontProduct, $user);
            }

            $product->update([
                'storefront_product_id' => $storefrontProduct->id,
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

            return $product->fresh('storefrontProduct');
        });
    }
}
