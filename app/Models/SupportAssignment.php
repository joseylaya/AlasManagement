<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportAssignment extends Model
{
    protected $fillable = ['conversation_id', 'admin_id', 'assigned_by', 'assigned_at', 'ended_at'];
    protected $casts = ['assigned_at' => 'datetime', 'ended_at' => 'datetime'];
}
