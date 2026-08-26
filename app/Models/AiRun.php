<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    use HasUuids;
    protected $fillable = ['conversation_id', 'trigger_message_id', 'provider', 'model', 'mode', 'status', 'prompt_tokens', 'completion_tokens', 'started_at', 'finished_at', 'error_code', 'error_message'];
    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    public function sources() { return $this->hasMany(AiRunSource::class, 'ai_run_id'); }
}
