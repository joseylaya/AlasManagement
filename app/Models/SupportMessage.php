<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasUuids;

    protected $fillable = ['conversation_id', 'sender_type', 'sender_user_id', 'content_type', 'content', 'payload', 'is_ai_generated', 'client_message_id', 'external_message_id', 'reply_to_message_id', 'delivery_status', 'edited_at'];
    protected $casts = ['payload' => 'array', 'is_ai_generated' => 'boolean', 'edited_at' => 'datetime'];

    public function conversation() { return $this->belongsTo(SupportConversation::class); }
    public function senderUser() { return $this->belongsTo(User::class, 'sender_user_id'); }
}
