<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportMessageAction extends Model
{
    use HasUuids;

    protected $fillable = ['conversation_id', 'message_id', 'customer_id', 'idempotency_key', 'action_type', 'product_id', 'variant_id', 'quantity', 'status', 'result_metadata'];
    protected $casts = ['result_metadata' => 'array'];
}
