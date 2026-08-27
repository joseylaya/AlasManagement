<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportAiJob extends Model
{
    use HasUuids;

    protected $fillable = ['conversation_id', 'status', 'priority', 'first_message_id', 'last_message_id', 'batch_started_at', 'ready_at', 'started_at', 'finished_at', 'attempt_count', 'model_used', 'error_code', 'error_message', 'generated_content', 'escalate_after_reply'];

    protected function casts(): array
    {
        return ['batch_started_at' => 'datetime', 'ready_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'escalate_after_reply' => 'boolean'];
    }

    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class);
    }

    public function firstMessage()
    {
        return $this->belongsTo(SupportMessage::class, 'first_message_id');
    }

    public function lastMessage()
    {
        return $this->belongsTo(SupportMessage::class, 'last_message_id');
    }
}
