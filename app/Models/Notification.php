<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'announcement_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
        'target_roles',
        'event_key',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'target_roles' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function isShared(): bool
    {
        return $this->user_id === null && !empty($this->target_roles);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (($user->status ?? 'active') !== 'active') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visible) use ($user): void {
            $visible->where('user_id', $user->id)
                ->orWhere(function (Builder $shared) use ($user): void {
                    $shared->whereNull('user_id')
                        ->where(function (Builder $audience) use ($user): void {
                            $audience->whereJsonContains('target_roles', 'all')
                                ->orWhereJsonContains('target_roles', $user->role);
                        });
                });
        });
    }

    public function markReadBy(User $user): void
    {
        if (!$this->isShared()) {
            $this->update(['is_read' => true]);

            return;
        }

        $this->reads()->firstOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()],
        );
    }

    public function applyReadStateFor(User $user): self
    {
        if ($this->isShared()) {
            $this->setAttribute('is_read', $this->reads->contains('user_id', $user->id));
        }

        return $this;
    }
}
