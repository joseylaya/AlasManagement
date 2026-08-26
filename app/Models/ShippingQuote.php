<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ShippingQuote extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['destination_snapshot' => 'array', 'parcel_snapshot' => 'array', 'expires_at' => 'datetime', 'amount' => 'decimal:2'];
}
