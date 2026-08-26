<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceArea extends Model
{
    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean'];
}
