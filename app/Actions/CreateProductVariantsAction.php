<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateProductVariantsAction
{
    public static function execute(array $data, array $variants, ?User $user = null): Collection
    {
        return DB::transaction(function () use ($data, $variants, $user) {
            $products = collect();
            $storefrontProductId = $data['storefront_product_id'] ?? null;

            foreach ($variants as $variant) {
                $product = CreateProductAction::execute(array_merge($data, $variant, [
                    'storefront_product_id' => $storefrontProductId,
                ]), $user);

                $storefrontProductId = $product->storefront_product_id;
                $products->push($product);
            }

            return $products;
        });
    }
}
