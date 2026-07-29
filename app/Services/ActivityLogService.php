<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $properties = null,
        ?User $user = null
    ): ActivityLog {
        $userId = $user ? $user->id : (Auth::check() ? Auth::id() : null);

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
