<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryProviderSetting extends Model
{
    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean', 'origin_address' => 'array'];
}
