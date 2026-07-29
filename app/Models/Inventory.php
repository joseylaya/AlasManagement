<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'current_stock',
        'min_stock_threshold',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock_threshold;
    }
}
