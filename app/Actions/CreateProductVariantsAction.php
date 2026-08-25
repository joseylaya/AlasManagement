<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductVariantsAction
{
    public static function execute(array $data, array $variants, ?User $user = null): Collection
    {
        return DB::transaction(function () use ($data, $variants, $user) {
            $products = collect();
            $storefrontProductId = $data['storefront_product_id'] ?? null;
            $reservedSkus = [];

            foreach ($variants as $variant) {
                $size = strtoupper(trim($variant['size']));
                $variantName = collect([$data['product_name'], $data['color'] ?? null, $size])
                    ->filter(fn ($part) => filled($part))
                    ->join(' - ');
                $sku = filled($variant['sku'] ?? null)
                    ? strtoupper(trim($variant['sku']))
                    : self::generateSku($data['product_name'], $data['color'] ?? null, $size, $reservedSkus);
                $reservedSkus[] = $sku;

                $product = CreateProductAction::execute(array_merge($data, $variant, [
                    'storefront_product_id' => $storefrontProductId,
                    'product_name' => $variantName,
                    'size' => $size,
                    'sku' => $sku,
                ]), $user);

                $storefrontProductId = $product->storefront_product_id;
                $products->push($product);
            }

            return $products;
        });
    }

    private static function generateSku(string $name, ?string $color, string $size, array $reservedSkus): string
    {
        $base = strtoupper(Str::slug(collect([$name, $color, $size])->filter()->join('-')));
        $base = substr($base ?: 'ALAS-PRODUCT', 0, 90);
        $sku = $base;
        $suffix = 2;

        while (in_array($sku, $reservedSkus, true) || Product::withTrashed()->where('sku', $sku)->exists()) {
            $sku = substr($base, 0, 96 - strlen((string) $suffix)).'-'.$suffix++;
        }

        return $sku;
    }
}
