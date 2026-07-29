<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnerDrawal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'drawal_number',
        'user_id',
        'amount',
        'drawal_date',
        'reason',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'drawal_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashTransaction()
    {
        return $this->hasOne(CashTransaction::class);
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
