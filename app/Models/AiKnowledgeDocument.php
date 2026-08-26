<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiKnowledgeDocument extends Model
{
    use HasUuids;
    protected $fillable = ['knowledge_base_id', 'previous_version_id', 'title', 'content', 'source_type', 'category', 'status', 'version', 'embedding_provider', 'embedding_model', 'index_error', 'indexed_at', 'archived_at', 'created_by', 'updated_by'];
    protected $casts = ['indexed_at' => 'datetime', 'archived_at' => 'datetime'];
    public function chunks() { return $this->hasMany(AiKnowledgeChunk::class, 'document_id'); }
    public function knowledgeBase() { return $this->belongsTo(AiKnowledgeBase::class, 'knowledge_base_id'); }
}
