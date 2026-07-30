<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Role Checks ──────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    // ─── Permission Helpers ───────────────────────────────────────

    /** Can view and modify financial data */
    public function canAccessFinance(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Staff may open the finance ledger, but never its sensitive summaries or write controls. */
    public function canViewFinance(): bool
    {
        return $this->isOwner() || $this->isManager() || $this->isStaff();
    }

    /** Can write/modify financial records (expenses, transactions) */
    public function canModifyFinance(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Can approve or reject orders */
    public function canApproveOrders(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Can change order operational status (confirmed, packed, shipped, etc.) */
    public function canManageOrderFulfillment(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Can adjust inventory stock levels */
    public function canAdjustInventory(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Can manage (create/edit/delete) products */
    public function canManageProducts(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Can manage user accounts */
    public function canManageUsers(): bool
    {
        return $this->isOwner();
    }

    /** Can change global settings */
    public function canManageSettings(): bool
    {
        return $this->isOwner();
    }

    /** Can permanently delete or archive protected records */
    public function canDeleteRecords(): bool
    {
        return $this->isOwner();
    }

    /** Can record owner withdrawals */
    public function canRecordWithdrawals(): bool
    {
        return $this->isOwner();
    }

    /** Can view all reports and analytics */
    public function canViewReports(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    /** Can cancel a confirmed or approved order */
    public function canCancelOrder(): bool
    {
        return $this->isOwner() || $this->isManager();
    }

    // ─── Relationships ────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
