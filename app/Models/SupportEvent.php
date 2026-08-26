<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportEvent extends Model
{
    use HasUuids;
    protected $fillable = ['conversation_id', 'event_type', 'actor_type', 'actor_id', 'metadata'];
    protected $casts = ['metadata' => 'array'];
}
