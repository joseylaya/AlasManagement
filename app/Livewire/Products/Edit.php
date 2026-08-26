<?php

namespace App\Livewire\Products;

use App\Actions\UpdateProductAction;
use App\Actions\SyncStorefrontProductImagesAction;
use App\Models\Product;
use App\Models\StorefrontProduct;
use App\Services\ProductImageStorageService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Product $product;

    public string $product_name = '';

    public ?int $storefront_product_id = null;

    public string $storefront_name = '';

    public string $storefront_slug = '';

    public string $storefront_description = '';

    public string $material = '';

    public bool $is_featured = false;

    public string $storefront_status = 'active';

    public array $photos = [];

    public array $existingImages = [];

    public array $removedImageIds = [];

    public string $sku = '';

    public string $category = '';

    public string $color = '';

    public string $size = '';

    public string $description = '';

    public $selling_price = 0;

    public $cost_price = 0;

    public int $min_stock_threshold = 10;

    public string $status = 'active';

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->product_name = $product->product_name;
        $this->storefront_product_id = $product->storefront_product_id;
        $this->storefront_name = $product->storefrontProduct?->name ?? $product->product_name;
        $this->storefront_slug = $product->storefrontProduct?->slug ?? '';
        $this->storefront_description = $product->storefrontProduct?->description ?? ($product->description ?? '');
        $this->material = $product->storefrontProduct?->material ?? '';
        $this->is_featured = $product->storefrontProduct?->is_featured ?? false;
        $this->storefront_status = $product->storefrontProduct?->status ?? 'active';
        $this->existingImages = $product->storefrontProduct?->images->map(fn ($image) => [
            'id' => $image->id,
            'url' => $image->image_url,
            'alt' => $image->alt_text,
        ])->values()->all() ?? [];
        $this->sku = $product->sku;
        $this->category = $product->category;
        $this->color = $product->color ?? '';
        $this->size = $product->size ?? '';
        $this->description = $product->description ?? '';
        $this->selling_price = $product->selling_price;
        $this->cost_price = $product->cost_price;
        $this->min_stock_threshold = $product->inventory ? $product->inventory->min_stock_threshold : 10;
        $this->status = $product->status;
    }

    protected function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'storefront_product_id' => 'required|exists:storefront_products,id',
            'storefront_name' => 'required|string|max:255',
            'storefront_slug' => 'required|string|max:255|alpha_dash|unique:storefront_products,slug,'.$this->storefront_product_id,
            'storefront_description' => 'nullable|string',
            'material' => 'nullable|string|max:120',
            'is_featured' => 'boolean',
            'storefront_status' => 'required|in:active,inactive,archived',
            'photos' => 'nullable|array|max:8',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            'removedImageIds' => 'array',
            'removedImageIds.*' => 'integer',
            'sku' => 'required|string|max:100|unique:products,sku,'.$this->product->id,
            'category' => 'required|string|max:100',
            'color' => 'nullable|string|max:100',
            'size' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'min_stock_threshold' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,archived',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $storage = app(ProductImageStorageService::class);
        if ($this->photos) {
            $storage->ensureBucket();
        }
        $uploads = collect($this->photos)->map(fn ($photo) => $storage->upload($photo, $this->storefront_slug ?: $this->product_name))->all();
        $removedUrls = collect($this->product->storefrontProduct?->images ?? [])->whereIn('id', $this->removedImageIds)->pluck('image_url');

        $product = UpdateProductAction::execute($this->product, [
            'product_name' => $this->product_name,
            'storefront_product_id' => $this->storefront_product_id,
            'storefront_name' => $this->storefront_name,
            'storefront_slug' => $this->storefront_slug,
            'storefront_description' => $this->storefront_description,
            'material' => $this->material,
            'is_featured' => $this->is_featured,
            'storefront_status' => $this->storefront_status,
            'image_url' => $uploads[0]['url'] ?? $this->product->image_url,
            'update_storefront_product' => true,
            'sku' => $this->sku,
            'category' => $this->category,
            'color' => $this->color,
            'size' => $this->size,
            'description' => $this->description,
            'selling_price' => $this->selling_price,
            'cost_price' => $this->cost_price,
            'min_stock_threshold' => $this->min_stock_threshold,
            'status' => $this->status,
        ]);

        SyncStorefrontProductImagesAction::execute($product->storefrontProduct, $uploads, $this->removedImageIds);
        $removedUrls->each(fn ($url) => $storage->deleteByPublicUrl($url));

        session()->flash('success', "Product {$this->product->product_name} updated successfully!");
        $this->redirect(route('products.index'), navigate: true);
    }

    public function removeExistingImage(int $imageId): void
    {
        $image = collect($this->existingImages)->firstWhere('id', $imageId);
        if (! $image) {
            return;
        }

        $this->removedImageIds[] = $imageId;
        $this->existingImages = collect($this->existingImages)->reject(fn ($item) => $item['id'] === $imageId)->values()->all();
    }

    public function removeNewPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function render()
    {
        return view('livewire.products.edit', [
            'storefrontProducts' => StorefrontProduct::orderBy('name')->get(),
        ])->layout('layouts.app', ['pageHeader' => 'Edit Product']);
    }

    public function updatedStorefrontProductId($value): void
    {
        $storefrontProduct = StorefrontProduct::find($value);
        if (! $storefrontProduct) {
            return;
        }

        $this->storefront_name = $storefrontProduct->name;
        $this->storefront_slug = $storefrontProduct->slug;
        $this->storefront_description = $storefrontProduct->description ?? '';
        $this->material = $storefrontProduct->material ?? '';
        $this->is_featured = $storefrontProduct->is_featured;
        $this->storefront_status = $storefrontProduct->status;
    }
}
