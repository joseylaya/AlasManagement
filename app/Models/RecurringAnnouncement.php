<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringAnnouncement extends Model
{
    protected $fillable = [
        'key', 'created_by', 'updated_by', 'target_role', 'title', 'message',
        'send_time', 'timezone', 'is_active', 'last_sent_on',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sent_on' => 'date',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
