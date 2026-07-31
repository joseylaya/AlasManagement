<?php

namespace App\Livewire\Products;

use App\Actions\CreateProductAction;
use Livewire\Component;

class Create extends Component
{
    public string $product_name = '';
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
        return view('livewire.products.create')->layout('layouts.app', ['pageHeader' => 'Create New Product']);
    }
}
