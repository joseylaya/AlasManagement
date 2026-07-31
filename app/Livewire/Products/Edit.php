<?php

namespace App\Livewire\Products;

use App\Actions\UpdateProductAction;
use App\Models\Product;
use Livewire\Component;

class Edit extends Component
{
    public Product $product;

    public string $product_name = '';
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
            'sku' => 'required|string|max:100|unique:products,sku,' . $this->product->id,
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
        return view('livewire.products.edit')->layout('layouts.app', ['pageHeader' => 'Edit Product']);
    }
}
