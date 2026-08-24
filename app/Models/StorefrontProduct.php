<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorefrontProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['slug', 'name', 'description', 'material', 'status', 'is_featured', 'sort_order', 'created_by', 'updated_by'];

    protected $casts = ['is_featured' => 'boolean', 'sort_order' => 'integer'];

    public function variants()
    {
        return $this->hasMany(Product::class);
    }

    public function images()
    {
        return $this->hasMany(StorefrontProductImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
