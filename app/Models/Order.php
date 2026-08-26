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
        'public_token',
        'checkout_idempotency_key',
        'currency',
        'commerce_mode',
        'paymongo_checkout_session_id',
        'paymongo_checkout_url',
        'paymongo_payment_intent_id',
        'paymongo_payment_method_id',
        'paymongo_payment_id',
        'paymongo_qr_image_url',
        'paymongo_qr_expires_at',
        'paymongo_payment_attempt',
        'payment_error_code',
        'paid_at',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_method',
        'shipping_address',
        'delivery_address_snapshot',
        'delivery_provider',
        'delivery_service',
        'shipping_quote_id',
        'shipping_quote_source',
        'shipping_status',
        'tracking_number',
        'tracking_url',
        'tracking_email_sent_at',
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
        'subtotal_amount',
        'shipping_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'delivery_address_snapshot' => 'array',
        'meetup_date' => 'date',
        'approved_at' => 'datetime',
        'server_updated_at' => 'datetime',
        'paid_at' => 'datetime',
        'paymongo_qr_expires_at' => 'datetime',
        'paymongo_payment_attempt' => 'integer',
        'tracking_email_sent_at' => 'datetime',
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
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->approval_status),
        };
    }

    public function approvalStatusColor(): string
    {
        return match ($this->approval_status) {
            'pending_approval' => 'orange',
            'approved' => 'emerald',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
