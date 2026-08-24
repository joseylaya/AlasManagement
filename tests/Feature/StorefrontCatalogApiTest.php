<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_groups_active_skus_as_variants_without_exposing_internal_costs(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $small = CreateProductAction::execute([
            'product_name' => 'ALAS Heavy Tee Black S', 'storefront_name' => 'ALAS Heavy Tee', 'storefront_slug' => 'alas-heavy-tee',
            'storefront_description' => 'Premium heavyweight cotton.', 'material' => '240 GSM Cotton', 'image_url' => 'https://example.com/tee.jpg',
            'sku' => 'ALAS-HEAVY-BLK-S', 'category' => 'T-Shirts', 'color' => 'Black', 'size' => 'S',
            'selling_price' => 750, 'cost_price' => 300, 'initial_stock' => 5,
        ], $manager);
        CreateProductAction::execute([
            'storefront_product_id' => $small->storefront_product_id, 'product_name' => 'ALAS Heavy Tee Black M',
            'sku' => 'ALAS-HEAVY-BLK-M', 'category' => 'T-Shirts', 'color' => 'Black', 'size' => 'M',
            'selling_price' => 750, 'cost_price' => 300, 'initial_stock' => 8,
        ], $manager);

        $response = $this->getJson('/api/storefront/products');

        $response->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.slug', 'alas-heavy-tee')
            ->assertJsonPath('data.0.material', '240 GSM Cotton')
            ->assertJsonCount(2, 'data.0.variants')
            ->assertJsonPath('data.0.variants.0.price_centavos', 75000)
            ->assertJsonPath('data.0.variants.1.available_quantity', 8)
            ->assertJsonMissingPath('data.0.variants.0.cost_price');
    }

    public function test_inactive_skus_and_storefront_products_are_not_public(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        CreateProductAction::execute([
            'product_name' => 'Hidden Tee', 'storefront_name' => 'Hidden Tee', 'sku' => 'ALAS-HIDDEN-S',
            'category' => 'T-Shirts', 'selling_price' => 750, 'cost_price' => 300, 'initial_stock' => 2, 'status' => 'inactive',
        ], $manager);

        $this->getJson('/api/storefront/products')->assertOk()->assertJsonPath('meta.count', 0);
    }
}
