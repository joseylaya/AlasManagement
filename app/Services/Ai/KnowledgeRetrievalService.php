<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Models\AiKnowledgeChunk;
use Illuminate\Support\Facades\DB;

class KnowledgeRetrievalService
{
    public function __construct(private AiProvider $provider) {}

    public function retrieve(string $query, int $limit = 5): array
    {
        $hasActiveKnowledge = AiKnowledgeChunk::query()->whereHas('document', fn ($q) => $q->where('status', 'ACTIVE')->where('embedding_model', config('services.ai.embedding_model')))->exists();
        if (! $hasActiveKnowledge) return [];
        $needle = $this->provider->embed($query, 'RETRIEVAL_QUERY');
        if (DB::getDriverName() === 'pgsql') {
            $vector = '['.implode(',', $needle).']';
            $minimum = (float) config('services.ai.minimum_similarity', 0.55);
            return AiKnowledgeChunk::query()->select('ai_knowledge_chunks.*')->selectRaw('1 - (embedding <=> ?::vector) as similarity_score', [$vector])
                ->whereHas('document', fn ($q) => $q->where('status', 'ACTIVE')->where('embedding_model', config('services.ai.embedding_model')))
                ->whereNotNull('embedding')->whereRaw('1 - (embedding <=> ?::vector) >= ?', [$vector, $minimum])->orderByRaw('embedding <=> ?::vector', [$vector])->limit($limit)->get()
                ->map(fn ($chunk) => ['chunk' => $chunk, 'score' => (float) $chunk->similarity_score])->all();
        }
        return AiKnowledgeChunk::query()->whereHas('document', fn ($q) => $q->where('status', 'ACTIVE')->where('embedding_model', config('services.ai.embedding_model')))->with('document:id,title,category,status')->get()
            ->map(fn ($chunk) => ['chunk' => $chunk, 'score' => $this->cosine($needle, $chunk->embedding ?? [])])
            ->filter(fn ($result) => $result['score'] >= (float) config('services.ai.minimum_similarity', 0.55))
            ->sortByDesc('score')->take($limit)->values()->all();
    }

    private function cosine(array $a, array $b): float
    {
        if (count($a) !== count($b) || $a === []) return 0.0;
        $dot = $aa = $bb = 0.0;
        foreach ($a as $i => $value) { $dot += $value * $b[$i]; $aa += $value ** 2; $bb += $b[$i] ** 2; }
        return $aa > 0 && $bb > 0 ? $dot / (sqrt($aa) * sqrt($bb)) : 0.0;
    }
}
