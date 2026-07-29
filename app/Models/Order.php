<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_method',
        'shipping_address',
        'meetup_date',
        'meetup_location',
        'order_status',
        'payment_status',
        'payment_method',
        'total_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'meetup_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
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
