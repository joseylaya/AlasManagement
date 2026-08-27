<?php

namespace App\Models;

use App\Enums\SupportConversationMode;
use App\Enums\SupportConversationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportConversation extends Model
{
    use HasUuids;

    protected $fillable = ['customer_id', 'channel', 'mode', 'status', 'assigned_admin_id', 'customer_unread_count', 'admin_unread_count', 'context', 'last_message_at', 'last_customer_message_at', 'last_admin_message_at', 'last_ai_message_at', 'taken_over_at', 'ai_resumed_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['mode' => SupportConversationMode::class, 'status' => SupportConversationStatus::class, 'context' => 'array', 'last_message_at' => 'datetime', 'last_customer_message_at' => 'datetime', 'last_admin_message_at' => 'datetime', 'last_ai_message_at' => 'datetime', 'taken_over_at' => 'datetime', 'ai_resumed_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(SupportCustomer::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function events()
    {
        return $this->hasMany(SupportEvent::class, 'conversation_id');
    }

    public function assignments()
    {
        return $this->hasMany(SupportAssignment::class, 'conversation_id');
    }

    public function aiJobs()
    {
        return $this->hasMany(SupportAiJob::class, 'conversation_id');
    }
}
