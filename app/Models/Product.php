<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_name',
        'storefront_product_id',
        'sku',
        'category',
        'color',
        'size',
        'description',
        'selling_price',
        'cost_price',
        'image_url',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function storefrontProduct()
    {
        return $this->belongsTo(StorefrontProduct::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) ($this->selling_price - $this->cost_price);
    }

    public function getCurrentStockAttribute(): int
    {
        return $this->inventory ? $this->inventory->current_stock : 0;
    }
}
