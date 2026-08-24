<?php

namespace App\Actions;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorefrontProduct;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public static function execute(array $data, ?User $user = null): Product
    {
        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($data, $user, $userId) {
            $storefrontProduct = ! empty($data['storefront_product_id'])
                ? StorefrontProduct::findOrFail($data['storefront_product_id'])
                : UpsertStorefrontProductAction::execute($data, null, $user);

            $product = Product::create([
                'storefront_product_id' => $storefrontProduct->id,
                'product_name' => $data['product_name'],
                'sku' => strtoupper(trim($data['sku'])),
                'category' => $data['category'] ?? 'Uncategorized',
                'color' => $data['color'] ?? null,
                'size' => $data['size'] ?? null,
                'description' => $data['description'] ?? null,
                'selling_price' => $data['selling_price'],
                'cost_price' => $data['cost_price'],
                'image_url' => $data['image_url'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $initialStock = isset($data['initial_stock']) ? (int) $data['initial_stock'] : 0;
            $minThreshold = isset($data['min_stock_threshold']) ? (int) $data['min_stock_threshold'] : 10;

            $inventory = Inventory::create([
                'product_id' => $product->id,
                'current_stock' => $initialStock,
                'min_stock_threshold' => $minThreshold,
                'status' => 'active',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($initialStock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'movement_type' => 'initial_stock',
                    'quantity' => $initialStock,
                    'reason' => 'Initial stock on product creation',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            ActivityLogService::log(
                'Product Created',
                "Created product {$product->product_name} ({$product->sku}) with initial stock of {$initialStock}.",
                $product
            );

            return $product;
        });
    }
}
