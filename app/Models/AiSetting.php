<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = ['enabled', 'provider', 'model', 'embedding_model', 'max_output_tokens', 'max_knowledge_results', 'max_recent_messages', 'provider_timeout_seconds', 'welcome_message', 'updated_by'];
    protected $casts = ['enabled' => 'boolean'];
}
