<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRunSource extends Model
{
    protected $fillable = ['ai_run_id', 'source_type', 'source_id', 'similarity_score', 'metadata'];
    protected $casts = ['metadata' => 'array'];
}
