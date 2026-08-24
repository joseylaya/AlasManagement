<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorefrontProductImage extends Model
{
    use SoftDeletes;

    protected $fillable = ['storefront_product_id', 'image_url', 'alt_text', 'sort_order', 'status', 'created_by', 'updated_by'];

    protected $casts = ['sort_order' => 'integer'];

    public function storefrontProduct()
    {
        return $this->belongsTo(StorefrontProduct::class);
    }
}
