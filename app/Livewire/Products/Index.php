<?php

namespace App\Livewire\Products;

use App\Actions\ArchiveProductAction;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedCategory = '';
    public string $selectedStatus = 'active';

    public function archiveProduct(int $productId): void
    {
        abort_unless(auth()->user()->canManageProducts(), 403);
        $product = Product::findOrFail($productId);
        ArchiveProductAction::execute($product);
        session()->flash('success', "Product {$product->product_name} was archived successfully.");
    }

    public function render()
    {
        $query = Product::with('inventory');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('product_name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->selectedCategory)) {
            $query->where('category', $this->selectedCategory);
        }

        if (!empty($this->selectedStatus)) {
            $query->where('status', $this->selectedStatus);
        }

        $products = $query->latest('id')->paginate(10);
        $categories = Product::distinct('category')->pluck('category')->filter();

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => $categories,
        ])->layout('layouts.app', ['pageHeader' => 'Product Catalog']);
    }
}
