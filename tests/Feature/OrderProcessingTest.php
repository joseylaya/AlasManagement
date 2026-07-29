<?php

namespace Tests\Feature;

use App\Actions\CancelOrderAction;
use App\Actions\CreateOrderAction;
use App\Actions\CreateProductAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_order_deducts_stock_and_stores_price_snapshot(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $product = CreateProductAction::execute([
            'product_name' => 'ALAS Streetwear Tee',
            'sku' => 'ALAS-ST-001',
            'selling_price' => 700.00,
            'cost_price' => 300.00,
            'initial_stock' => 20,
        ], $user);

        $order = CreateOrderAction::execute(
            [
                'customer_name' => 'John Doe',
                'delivery_method' => 'shipping',
                'shipping_address' => 'Manila City',
                'order_status' => 'pending',
            ],
            [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 700.00],
            ],
            $user
        );

        $this->assertEquals(18, $product->fresh()->current_stock);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'ALAS Streetwear Tee',
            'sku' => 'ALAS-ST-001',
            'unit_price' => 700.00,
            'quantity' => 2,
            'subtotal' => 1400.00,
        ]);
    }

    public function test_cancelling_order_restores_inventory_stock(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $product = CreateProductAction::execute([
            'product_name' => 'ALAS Jogger Pants',
            'sku' => 'ALAS-JOG-001',
            'selling_price' => 900.00,
            'cost_price' => 400.00,
            'initial_stock' => 10,
        ], $user);

        $order = CreateOrderAction::execute(
            ['customer_name' => 'Jane Smith', 'order_status' => 'pending'],
            [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 900.00]],
            $user
        );

        $this->assertEquals(7, $product->fresh()->current_stock);

        CancelOrderAction::execute($order, 'Customer requested cancellation', $user);

        $this->assertEquals('cancelled', $order->fresh()->order_status);
        $this->assertEquals(10, $product->fresh()->current_stock);
    }
}
