<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Models\Product;
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
    }
}
