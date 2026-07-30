<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class ArchiveProductAction
{
    public static function execute(Product $product, ?User $user = null): Product
    {
        $actor = $user ?? Auth::user();
        if (! $actor || ! $actor->canManageProducts()) {
            abort(403, 'You do not have permission to archive products.');
        }
        $userId = $actor->id;

        $product->update([
            'status' => 'archived',
            'updated_by' => $userId,
        ]);

        ActivityLogService::log(
            'Product Archived',
            "Archived product {$product->product_name} ({$product->sku}).",
            $product
        );

        return $product;
    }
}
