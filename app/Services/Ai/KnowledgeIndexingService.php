<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class KnowledgeIndexingService
{
    public function __construct(private AiProvider $provider) {}

    public function index(AiKnowledgeDocument $document): void
    {
        $document->update(['status' => 'PROCESSING', 'index_error' => null]);
        try {
            $chunks = $this->chunks($document->content);
            $embedded = [];
            foreach ($chunks as $index => $content) {
                $embedded[] = ['id' => Str::uuid(), 'document_id' => $document->id, 'chunk_index' => $index, 'content' => $content, 'metadata' => json_encode(['title' => $document->title, 'category' => $document->category]), 'embedding' => json_encode($this->provider->embed($content)), 'embedding_model' => config('services.ai.embedding_model'), 'created_at' => now(), 'updated_at' => now()];
            }
            DB::transaction(function () use ($document, $embedded) {
                $document->chunks()->delete();
                AiKnowledgeChunk::insert($embedded);
                $document->update(['status' => 'ACTIVE', 'embedding_provider' => $this->provider->name(), 'embedding_model' => config('services.ai.embedding_model'), 'indexed_at' => now()]);
                if ($document->previous_version_id) AiKnowledgeDocument::whereKey($document->previous_version_id)->where('status', 'ACTIVE')->update(['status' => 'ARCHIVED', 'archived_at' => now()]);
            });
        } catch (Throwable $exception) {
            $document->update(['status' => 'FAILED', 'index_error' => Str::limit($exception->getMessage(), 1000)]);
            throw $exception;
        }
    }

    private function chunks(string $content): array
    {
        $paragraphs = collect(preg_split('/\n\s*\n/', trim($content)) ?: [])
            ->flatMap(fn ($paragraph) => $this->sentenceSegments(trim($paragraph)))
            ->all();
        $chunks = []; $current = '';
        foreach ($paragraphs as $paragraph) {
            if (mb_strlen($current.'\n\n'.$paragraph) > 1400 && $current !== '') { $chunks[] = trim($current); $current = ''; }
            $current .= ($current === '' ? '' : "\n\n").trim($paragraph);
        }
        if ($current !== '') $chunks[] = trim($current);
        return $chunks ?: [trim($content)];
    }

    private function sentenceSegments(string $paragraph): array
    {
        if ($paragraph === '') return [];

        // Keep sentence-ending punctuation with its sentence and support
        // multilingual text, closing quotes, and paragraph-final sentences.
        $sentences = preg_split('/(?:(?<=[.!?])|(?<=[.!?]["”’]))\s+/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [$paragraph];

        return collect($sentences)->flatMap(function ($sentence) {
            $parts = [];
            $sentence = trim($sentence);
            while (mb_strlen($sentence) > 1400) {
                $candidate = mb_substr($sentence, 0, 1400);
                $breakAt = mb_strrpos($candidate, ' ') ?: 1400;
                if ($breakAt < 900) $breakAt = 1400;
                $parts[] = trim(mb_substr($sentence, 0, $breakAt));
                $sentence = trim(mb_substr($sentence, $breakAt));
            }
            if ($sentence !== '') $parts[] = $sentence;
            return $parts;
        })->all();
    }
}
