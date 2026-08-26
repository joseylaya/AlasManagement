<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Actions\CreateProductVariantsAction;
use App\Actions\SyncStorefrontProductImagesAction;
use App\Livewire\Products\Create;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_size_variants_are_created_atomically_under_one_storefront_product(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $products = CreateProductVariantsAction::execute([
            'product_name' => 'ALAS Heavy Tee',
            'storefront_name' => 'ALAS Heavy Tee',
            'category' => 'T-Shirts',
            'color' => 'Black',
            'selling_price' => 750,
            'cost_price' => 300,
            'min_stock_threshold' => 5,
        ], [
            ['size' => 'S', 'initial_stock' => 4],
            ['size' => 'M', 'initial_stock' => 7],
        ], $user);

        $this->assertCount(2, $products);
        $this->assertSame($products[0]->storefront_product_id, $products[1]->storefront_product_id);
        $this->assertDatabaseHas('products', ['sku' => 'ALAS-HEAVY-TEE-BLACK-S', 'product_name' => 'ALAS Heavy Tee - Black - S', 'size' => 'S']);
        $this->assertDatabaseHas('products', ['sku' => 'ALAS-HEAVY-TEE-BLACK-M', 'product_name' => 'ALAS Heavy Tee - Black - M', 'size' => 'M']);
        $this->assertDatabaseHas('inventories', ['product_id' => $products[0]->id, 'current_stock' => 4]);
        $this->assertDatabaseHas('inventories', ['product_id' => $products[1]->id, 'current_stock' => 7]);
        $this->assertDatabaseCount('storefront_products', 1);
    }

    public function test_create_form_requires_unique_sizes(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('product_name', 'ALAS Heavy Tee')
            ->set('variants', [
                ['size' => 'S', 'initial_stock' => 2],
                ['size' => 's', 'initial_stock' => 3],
            ])
            ->call('save')
            ->assertHasErrors(['variants.1.size']);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('storefront_products', 0);
    }

    public function test_multiple_storefront_images_are_ordered_and_primary_image_is_shared_by_variants(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $product = CreateProductAction::execute([
            'product_name' => 'ALAS Gallery Tee', 'sku' => 'ALAS-GALLERY-L', 'category' => 'T-Shirts',
            'size' => 'L', 'selling_price' => 750, 'cost_price' => 300,
        ], $user);

        $this->actingAs($user);
        SyncStorefrontProductImagesAction::execute($product->storefrontProduct, [
            ['url' => 'https://example.supabase.co/storage/v1/object/public/product-images/gallery/front.webp'],
            ['url' => 'https://example.supabase.co/storage/v1/object/public/product-images/gallery/detail.webp'],
        ]);

        $this->assertDatabaseHas('storefront_product_images', ['storefront_product_id' => $product->storefront_product_id, 'image_url' => 'https://example.supabase.co/storage/v1/object/public/product-images/gallery/front.webp', 'sort_order' => 0]);
        $this->assertDatabaseHas('storefront_product_images', ['storefront_product_id' => $product->storefront_product_id, 'image_url' => 'https://example.supabase.co/storage/v1/object/public/product-images/gallery/detail.webp', 'sort_order' => 1]);
        $this->assertSame('https://example.supabase.co/storage/v1/object/public/product-images/gallery/front.webp', $product->fresh()->image_url);
    }
}
