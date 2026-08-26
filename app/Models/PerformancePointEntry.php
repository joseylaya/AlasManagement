<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformancePointEntry extends Model
{
    public const ACTIVITY_SUBMITTED = 'activity_submitted';
    public const ORDER_SUBMITTED = 'order_submitted';
    public const ORDER_COMPLETED = 'order_completed';

    protected $fillable = [
        'user_id', 'source_type', 'source_id', 'event', 'points', 'awarded_at',
    ];

    protected $casts = ['awarded_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
