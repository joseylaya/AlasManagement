<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;

use Exception;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $filterLowStock = false;

    // Stock Movement Modal State
    public bool $showMovementModal = false;
    public ?int $selectedProductId = null;
    public string $movementActionType = 'add'; // 'add' or 'adjust'
    public int $stockQuantity = 1;
    public string $reason = '';
    public string $referenceNumber = '';

    // History Modal State
    public bool $showHistoryModal = false;
    public ?Product $historyProduct = null;

    protected array $rules = [
        'selectedProductId' => 'required|exists:products,id',
        'reason' => 'required|string|min:3|max:255',
    ];

    public function openAddStockModal(int $productId): void
    {
        abort_unless(auth()->user()->canAdjustInventory(), 403);
        $this->selectedProductId = $productId;
        $this->movementActionType = 'add';
        $this->stockQuantity = 10;
        $this->reason = 'Supplier delivery arrival';
        $this->referenceNumber = '';
        $this->showMovementModal = true;
    }

    public function openAdjustStockModal(int $productId): void
    {
        abort_unless(auth()->user()->canAdjustInventory(), 403);
        $product = Product::with('inventory')->findOrFail($productId);
        $this->selectedProductId = $productId;
        $this->movementActionType = 'adjust';
        $this->stockQuantity = $product->current_stock;
        $this->reason = 'Inventory physical count correction';
        $this->referenceNumber = '';
        $this->showMovementModal = true;
    }

    public function processStockMovement(): void
    {
        abort_unless(auth()->user()->canAdjustInventory(), 403);
        $this->validate();

        $product = Product::findOrFail($this->selectedProductId);

        try {
            if ($this->movementActionType === 'add') {
                InventoryService::addStock($product, $this->stockQuantity, $this->reason, null, $this->referenceNumber);
                session()->flash('success', "Added {$this->stockQuantity} units to {$product->product_name}.");
            } else {
                InventoryService::adjustStock($product, $this->stockQuantity, $this->reason);
                session()->flash('success', "Adjusted stock for {$product->product_name} to {$this->stockQuantity}.");
            }

            $this->resetModal();
        } catch (Exception $e) {
            $this->addError('reason', $e->getMessage());
        }
    }

    public function viewHistory(int $productId): void
    {
        $this->historyProduct = Product::with(['stockMovements.user'])->findOrFail($productId);
        $this->showHistoryModal = true;
    }

    public function resetModal(): void
    {
        $this->showMovementModal = false;
        $this->selectedProductId = null;
        $this->reason = '';
        $this->referenceNumber = '';
    }

    public function render()
    {
        $query = Inventory::with('product')
            ->whereHas('product', function ($q) {
                $q->where('status', '!=', 'archived');
                if (!empty($this->search)) {
                    $q->where('product_name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                }
            });

        if ($this->filterLowStock) {
            $query->whereColumn('current_stock', '<=', 'min_stock_threshold');
        }

        $inventories = $query->latest('updated_at')->paginate(10);
        $selectedProduct = $this->selectedProductId ? Product::find($this->selectedProductId) : null;

        $movementsHistory = $this->historyProduct 
            ? StockMovement::with('user')->where('product_id', $this->historyProduct->id)->latest('id')->get() 
            : collect();

        return view('livewire.inventory.index', [
            'inventories' => $inventories,
            'selectedProduct' => $selectedProduct,
            'movementsHistory' => $movementsHistory,
            'canAdjustInventory' => auth()->user()->canAdjustInventory(),
        ])->layout('layouts.app', ['pageHeader' => 'Stock & Inventory Control']);
    }
}
