<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Add stock to inventory. Creates a StockMovement record and increments current_stock.
     */
    public static function addStock(
        Product $product,
        int $quantity,
        string $reason,
        ?User $user = null,
        ?string $reference = null,
        string $movementType = 'addition'  // FIX: was 'stock_addition', must match DB enum
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("Stock addition quantity must be greater than zero.");
        }

        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($product, $quantity, $reason, $userId, $reference, $movementType) {
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id],
                ['current_stock' => 0, 'min_stock_threshold' => 10, 'created_by' => $userId]
            );

            $movement = StockMovement::create([
                'product_id'       => $product->id,
                'user_id'          => $userId,
                'movement_type'    => $movementType,
                'quantity'         => $quantity,
                'reason'           => $reason,
                'reference_number' => $reference,
                'created_by'       => $userId,
                'updated_by'       => $userId,
            ]);

            $inventory->increment('current_stock', $quantity);

            ActivityLogService::log(
                'Stock Added',
                "Added {$quantity} units to {$product->product_name} ({$product->sku}). Reason: {$reason}",
                $product,
                ['quantity' => $quantity, 'new_stock' => $inventory->fresh()->current_stock]
            );

            return $movement;
        });
    }

    /**
     * Deduct stock from inventory. Prevents negative stock. Creates a StockMovement record.
     */
    public static function deductStock(
        Product $product,
        int $quantity,
        string $reason,
        ?User $user = null,
        ?string $reference = null,
        string $movementType = 'sale'
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("Stock deduction quantity must be greater than zero.");
        }

        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($product, $quantity, $reason, $userId, $reference, $movementType) {
            // FIX: Authoritative stock check directly from DB with lock to prevent race conditions
            $inventory = Inventory::where('product_id', $product->id)->lockForUpdate()->firstOrCreate(
                ['product_id' => $product->id],
                ['current_stock' => 0, 'min_stock_threshold' => 10, 'created_by' => $userId]
            );

            if ($inventory->current_stock < $quantity) {
                throw new Exception("Insufficient stock for {$product->product_name}. Available: {$inventory->current_stock}, Requested: {$quantity}.");
            }

            $movement = StockMovement::create([
                'product_id'       => $product->id,
                'user_id'          => $userId,
                'movement_type'    => $movementType,
                'quantity'         => -$quantity,
                'reason'           => $reason,
                'reference_number' => $reference,
                'created_by'       => $userId,
                'updated_by'       => $userId,
            ]);

            $inventory->decrement('current_stock', $quantity);
            $freshInventory = $inventory->fresh();

            ActivityLogService::log(
                'Stock Deducted',
                "Deducted {$quantity} units from {$product->product_name} ({$product->sku}). Reason: {$reason}",
                $product,
                ['quantity' => $quantity, 'new_stock' => $freshInventory->current_stock]
            );

            if ($freshInventory->is_low_stock) {
                NotificationService::notifyLowStock($product, $freshInventory->current_stock, $freshInventory->min_stock_threshold);
            }

            return $movement;
        });
    }

    /**
     * Adjust stock to a specific target quantity (recount). Logs the difference.
     */
    public static function adjustStock(
        Product $product,
        int $newStock,
        string $reason,
        ?User $user = null
    ): StockMovement {
        if ($newStock < 0) {
            throw new Exception("Stock cannot be negative.");
        }

        $userId = $user ? $user->id : Auth::id();

        return DB::transaction(function () use ($product, $newStock, $reason, $userId) {
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id],
                ['current_stock' => 0, 'min_stock_threshold' => 10, 'created_by' => $userId]
            );

            $oldStock = $inventory->current_stock; // FIX: Capture BEFORE update
            $diff = $newStock - $oldStock;

            if ($diff === 0) {
                throw new Exception("New stock count is identical to the current stock ({$oldStock}). No change recorded.");
            }

            $movement = StockMovement::create([
                'product_id'    => $product->id,
                'user_id'       => $userId,
                'movement_type' => 'adjustment',
                'quantity'      => $diff,
                'reason'        => $reason,
                'created_by'    => $userId,
                'updated_by'    => $userId,
            ]);

            $inventory->update(['current_stock' => $newStock, 'updated_by' => $userId]);
            $freshInventory = $inventory->fresh();

            // FIX: Use $oldStock (captured before update) not $inventory->current_stock (already updated)
            ActivityLogService::log(
                'Stock Adjusted',
                "Adjusted stock for {$product->product_name} ({$product->sku}) from {$oldStock} to {$newStock}. Difference: {$diff}. Reason: {$reason}",
                $product,
                ['old_stock' => $oldStock, 'new_stock' => $newStock, 'diff' => $diff]
            );

            if ($freshInventory->is_low_stock) {
                NotificationService::notifyLowStock($product, $freshInventory->current_stock, $freshInventory->min_stock_threshold);
            }

            return $movement;
        });
    }
}
