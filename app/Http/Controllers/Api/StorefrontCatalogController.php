<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StorefrontProduct;
use Illuminate\Http\JsonResponse;

class StorefrontCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $products = $this->catalogQuery()->get()->map(fn (StorefrontProduct $product) => $this->serialize($product));

        return response()->json(['data' => $products, 'meta' => ['count' => $products->count()]])
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->catalogQuery()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $this->serialize($product)])
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }

    private function catalogQuery()
    {
        return StorefrontProduct::query()
            ->where('status', 'active')
            ->whereHas('variants', fn ($query) => $query->where('status', 'active'))
            ->with([
                'images' => fn ($query) => $query->where('status', 'active'),
                'variants' => fn ($query) => $query->where('status', 'active')->with('inventory')->orderBy('id'),
            ])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    private function serialize(StorefrontProduct $product): array
    {
        $fallbackImage = $product->variants->first(fn ($variant) => filled($variant->image_url))?->image_url;
        $images = $product->images->map(fn ($image) => [
            'url' => $image->image_url,
            'alt' => $image->alt_text ?: $product->name,
        ])->values();

        if ($images->isEmpty() && $fallbackImage) {
            $images->push(['url' => $fallbackImage, 'alt' => $product->name]);
        }

        return [
            'id' => (string) $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'description' => $product->description ?? '',
            'material' => $product->material,
            'is_featured' => $product->is_featured,
            'images' => $images,
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => (string) $variant->id,
                'sku' => $variant->sku,
                'category' => $variant->category,
                'color' => $variant->color,
                'size' => $variant->size,
                'price_centavos' => (int) round(((float) $variant->selling_price) * 100),
                'available_quantity' => $variant->inventory?->current_stock ?? 0,
            ])->values(),
        ];
    }
}
