<?php

namespace App\Livewire\Products;

use App\Actions\CreateProductAction;
use App\Models\StorefrontProduct;
use Livewire\Component;

class Create extends Component
{
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

    public string $category = 'T-Shirts';

    public string $color = '';

    public string $size = 'L';

    public string $description = '';

    public $selling_price = 750.00;

    public $cost_price = 300.00;

    public int $initial_stock = 20;

    public int $min_stock_threshold = 10;

    protected array $rules = [
        'product_name' => 'required|string|max:255',
        'storefront_product_id' => 'nullable|exists:storefront_products,id',
        'storefront_name' => 'required_without:storefront_product_id|nullable|string|max:255',
        'storefront_slug' => 'nullable|string|max:255|alpha_dash|unique:storefront_products,slug',
        'storefront_description' => 'nullable|string',
        'material' => 'nullable|string|max:120',
        'is_featured' => 'boolean',
        'storefront_status' => 'required|in:active,inactive,archived',
        'image_url' => 'nullable|url|max:2048',
        'sku' => 'required|string|max:100|unique:products,sku',
        'category' => 'required|string|max:100',
        'color' => 'nullable|string|max:100',
        'size' => 'nullable|string|max:50',
        'description' => 'nullable|string',
        'selling_price' => 'required|numeric|min:0',
        'cost_price' => 'required|numeric|min:0',
        'initial_stock' => 'required|integer|min:0',
        'min_stock_threshold' => 'required|integer|min:0',
    ];

    public function save(): void
    {
        $this->validate();

        $product = CreateProductAction::execute([
            'product_name' => $this->product_name,
            'storefront_product_id' => $this->storefront_product_id,
            'storefront_name' => $this->storefront_name ?: $this->product_name,
            'storefront_slug' => $this->storefront_slug,
            'storefront_description' => $this->storefront_description ?: $this->description,
            'material' => $this->material,
            'is_featured' => $this->is_featured,
            'storefront_status' => $this->storefront_status,
            'image_url' => $this->image_url ?: null,
            'sku' => $this->sku,
            'category' => $this->category,
            'color' => $this->color,
            'size' => $this->size,
            'description' => $this->description,
            'selling_price' => $this->selling_price,
            'cost_price' => $this->cost_price,
            'initial_stock' => $this->initial_stock,
            'min_stock_threshold' => $this->min_stock_threshold,
        ]);

        session()->flash('success', "Product {$product->product_name} ({$product->sku}) created successfully!");
        $this->redirect(route('products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.products.create', [
            'storefrontProducts' => StorefrontProduct::where('status', 'active')->orderBy('name')->get(),
        ])->layout('layouts.app', ['pageHeader' => 'Create New Product']);
    }
}
