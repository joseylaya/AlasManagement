<?php

namespace App\Actions;

use App\Models\StorefrontProduct;
use App\Models\StorefrontProductImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SyncStorefrontProductImagesAction
{
    public static function execute(StorefrontProduct $product, array $uploads, array $removeIds = []): void
    {
        DB::transaction(function () use ($product, $uploads, $removeIds) {
            $userId = Auth::id();
            if ($removeIds) {
                $product->images()->whereKey($removeIds)->update(['updated_by' => $userId]);
                $product->images()->whereKey($removeIds)->delete();
            }

            $nextOrder = ((int) $product->images()->max('sort_order')) + 1;
            foreach ($uploads as $upload) {
                StorefrontProductImage::create([
                    'storefront_product_id' => $product->id,
                    'image_url' => $upload['url'],
                    'alt_text' => $product->name,
                    'sort_order' => $nextOrder++,
                    'status' => 'active',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            $images = $product->images()->where('status', 'active')->get();
            foreach ($images as $index => $image) {
                $image->update(['sort_order' => $index]);
            }

            $product->variants()->update(['image_url' => $images->first()?->image_url, 'updated_by' => $userId]);
        });
    }
}
