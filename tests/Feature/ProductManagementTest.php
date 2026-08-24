<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creation_automatically_initializes_inventory(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $productData = [
            'product_name' => 'ALAS Test Oversized Tee',
            'sku' => 'ALAS-TST-BLK-L',
            'category' => 'T-Shirts',
            'selling_price' => 750.00,
            'cost_price' => 300.00,
            'initial_stock' => 25,
            'min_stock_threshold' => 10,
        ];

        $product = CreateProductAction::execute($productData, $user);

        $this->assertDatabaseHas('products', [
            'sku' => 'ALAS-TST-BLK-L',
            'selling_price' => 750.00,
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'current_stock' => 25,
            'min_stock_threshold' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'initial_stock',
            'quantity' => 25,
        ]);

        $this->assertNotNull($product->storefront_product_id);
        $this->assertDatabaseHas('storefront_products', [
            'id' => $product->storefront_product_id,
            'name' => 'ALAS Test Oversized Tee',
            'status' => 'active',
        ]);
    }

    public function test_multiple_skus_can_share_one_storefront_product_without_changing_inventory_ownership(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $first = CreateProductAction::execute([
            'product_name' => 'ALAS Tee Black Small', 'storefront_name' => 'ALAS Tee', 'sku' => 'ALAS-TEE-BLK-S',
            'category' => 'T-Shirts', 'color' => 'Black', 'size' => 'S', 'selling_price' => 750, 'cost_price' => 300, 'initial_stock' => 4,
        ], $user);
        $second = CreateProductAction::execute([
            'storefront_product_id' => $first->storefront_product_id, 'product_name' => 'ALAS Tee Black Medium', 'sku' => 'ALAS-TEE-BLK-M',
            'category' => 'T-Shirts', 'color' => 'Black', 'size' => 'M', 'selling_price' => 750, 'cost_price' => 300, 'initial_stock' => 7,
        ], $user);

        $this->assertSame($first->storefront_product_id, $second->storefront_product_id);
        $this->assertDatabaseHas('inventories', ['product_id' => $first->id, 'current_stock' => 4]);
        $this->assertDatabaseHas('inventories', ['product_id' => $second->id, 'current_stock' => 7]);
    }
}
