<?php

namespace App\Livewire\Products;

use App\Actions\CreateProductVariantsAction;
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

    public string $category = 'T-Shirts';

    public string $color = '';

    public string $description = '';

    public $selling_price = 750.00;

    public $cost_price = 300.00;

    public int $min_stock_threshold = 10;

    public array $variants = [
        ['size' => 'L', 'sku' => '', 'initial_stock' => 20],
    ];

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
        'category' => 'required|string|max:100',
        'color' => 'nullable|string|max:100',
        'description' => 'nullable|string',
        'selling_price' => 'required|numeric|min:0',
        'cost_price' => 'required|numeric|min:0',
        'min_stock_threshold' => 'required|integer|min:0',
        'variants' => 'required|array|min:1',
        'variants.*.size' => 'required|string|max:50|distinct:ignore_case',
        'variants.*.sku' => 'required|string|max:100|distinct:ignore_case|unique:products,sku',
        'variants.*.initial_stock' => 'required|integer|min:0',
    ];

    public function addVariant(): void
    {
        $this->variants[] = ['size' => '', 'sku' => '', 'initial_stock' => 0];
    }

    public function removeVariant(int $index): void
    {
        if (count($this->variants) === 1) {
            return;
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(): void
    {
        $this->validate();

        $products = CreateProductVariantsAction::execute([
            'product_name' => $this->product_name,
            'storefront_product_id' => $this->storefront_product_id,
            'storefront_name' => $this->storefront_name ?: $this->product_name,
            'storefront_slug' => $this->storefront_slug,
            'storefront_description' => $this->storefront_description ?: $this->description,
            'material' => $this->material,
            'is_featured' => $this->is_featured,
            'storefront_status' => $this->storefront_status,
            'image_url' => $this->image_url ?: null,
            'category' => $this->category,
            'color' => $this->color,
            'description' => $this->description,
            'selling_price' => $this->selling_price,
            'cost_price' => $this->cost_price,
            'min_stock_threshold' => $this->min_stock_threshold,
        ], $this->variants);

        $count = $products->count();
        session()->flash('success', "{$this->product_name} created successfully with {$count} size ".str('variant')->plural($count).'!');
        $this->redirect(route('products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.products.create', [
            'storefrontProducts' => StorefrontProduct::where('status', 'active')->orderBy('name')->get(),
        ])->layout('layouts.app', ['pageHeader' => 'Create New Product']);
    }
}
