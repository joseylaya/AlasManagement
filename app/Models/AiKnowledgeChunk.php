<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiKnowledgeChunk extends Model
{
    use HasUuids;
    protected $fillable = ['document_id', 'chunk_index', 'content', 'metadata', 'embedding', 'embedding_model'];
    protected $casts = ['metadata' => 'array', 'embedding' => 'array'];
    public function document() { return $this->belongsTo(AiKnowledgeDocument::class, 'document_id'); }
}
