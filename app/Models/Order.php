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
        'client_uuid',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_method',
        'shipping_address',
        'meetup_date',
        'meetup_location',
        'order_status',
        'approval_status',
        'record_version',
        'server_updated_at',
        'sync_source',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'payment_status',
        'payment_method',
        'total_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'meetup_date'  => 'date',
        'approved_at'  => 'datetime',
        'server_updated_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────

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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Approval Status Helpers ──────────────────────────────────

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Can the given user approve or reject this order?
     */
    public function canBeApprovedBy(User $user): bool
    {
        return $this->isPendingApproval() && $user->canApproveOrders();
    }

    /**
     * Can the given user transition the operational status of this order?
     * Order must be approved first.
     */
    public function canTransitionStatus(User $user): bool
    {
        return $this->isApproved() && ($user->isOwner() || $user->isManager());
    }

    // ─── Approval Status Label ────────────────────────────────────

    public function approvalStatusLabel(): string
    {
        return match ($this->approval_status) {
            'pending_approval' => 'Pending Approval',
            'approved'         => 'Approved',
            'rejected'         => 'Rejected',
            default            => ucfirst($this->approval_status),
        };
    }

    public function approvalStatusColor(): string
    {
        return match ($this->approval_status) {
            'pending_approval' => 'orange',
            'approved'         => 'emerald',
            'rejected'         => 'red',
            default            => 'gray',
        };
    }
}
