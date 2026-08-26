<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiKnowledgeBase extends Model
{
    use HasUuids;
    protected $fillable = ['name', 'description', 'status', 'created_by', 'updated_by'];
    public function documents() { return $this->hasMany(AiKnowledgeDocument::class, 'knowledge_base_id'); }
}
