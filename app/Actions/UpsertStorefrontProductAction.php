<?php

namespace App\Actions;

use App\Models\StorefrontProduct;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UpsertStorefrontProductAction
{
    public static function execute(array $data, ?StorefrontProduct $storefrontProduct = null, ?User $user = null): StorefrontProduct
    {
        $userId = $user?->id ?? Auth::id();
        $name = trim($data['storefront_name'] ?? $data['product_name']);
        $slug = trim($data['storefront_slug'] ?? '');

        if ($slug === '') {
            $baseSlug = Str::slug($name) ?: 'product';
            $slug = $baseSlug;
            $suffix = 2;
            while (StorefrontProduct::withTrashed()->where('slug', $slug)->when($storefrontProduct, fn ($query) => $query->whereKeyNot($storefrontProduct->id))->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }
        }

        $values = [
            'slug' => $slug,
            'name' => $name,
            'description' => $data['storefront_description'] ?? $data['description'] ?? null,
            'material' => $data['material'] ?? null,
            'status' => $data['storefront_status'] ?? 'active',
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'updated_by' => $userId,
        ];

        if ($storefrontProduct) {
            $storefrontProduct->update($values);

            return $storefrontProduct->fresh();
        }

        return StorefrontProduct::create($values + ['created_by' => $userId]);
    }
}
