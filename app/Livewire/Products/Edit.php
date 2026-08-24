<?php

namespace App\Livewire\Products;

use App\Actions\UpdateProductAction;
use App\Models\Product;
use App\Models\StorefrontProduct;
use Livewire\Component;

class Edit extends Component
{
    public Product $product;

    public string $product_name = '';

    public ?int $storefront_product_id = null;

    public string $storefront_name = '';

    public string $storefront_slug = '';

    public string $storefront_description = '';

    public string $material = '';

    public bool $is_featured = false;

    public string $storefront_status = 'active';

    public string $image_url = '';

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
        $this->image_url = $product->image_url ?? '';
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
            'image_url' => 'nullable|url|max:2048',
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

        UpdateProductAction::execute($this->product, [
            'product_name' => $this->product_name,
            'storefront_product_id' => $this->storefront_product_id,
            'storefront_name' => $this->storefront_name,
            'storefront_slug' => $this->storefront_slug,
            'storefront_description' => $this->storefront_description,
            'material' => $this->material,
            'is_featured' => $this->is_featured,
            'storefront_status' => $this->storefront_status,
            'image_url' => $this->image_url ?: null,
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

        session()->flash('success', "Product {$this->product->product_name} updated successfully!");
        $this->redirect(route('products.index'), navigate: true);
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
