<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Services\InventoryService;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_cannot_become_negative(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $product = CreateProductAction::execute([
            'product_name' => 'Limited Edition Cap',
            'sku' => 'ALAS-CAP-001',
            'selling_price' => 500.00,
            'cost_price' => 200.00,
            'initial_stock' => 3,
        ], $user);

        $this->expectException(Exception::class);
        InventoryService::deductStock($product, 5, 'Excess checkout attempt', $user);
    }

    public function test_stock_adjustment_creates_movement_and_log(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $product = CreateProductAction::execute([
            'product_name' => 'ALAS Hooded Jacket',
            'sku' => 'ALAS-HOD-001',
            'selling_price' => 1200.00,
            'cost_price' => 500.00,
            'initial_stock' => 10,
        ], $user);

        InventoryService::adjustStock($product, 15, 'Restock recount', $user);

        $this->assertEquals(15, $product->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'quantity' => 5,
        ]);
    }
}
