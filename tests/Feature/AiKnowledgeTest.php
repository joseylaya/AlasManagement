<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\AiKnowledgeBase;
use App\Models\AiKnowledgeDocument;
use App\Services\Ai\KnowledgeIndexingService;
use App\Services\Ai\KnowledgeRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(AiProvider::class, new class implements AiProvider {
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array { return ['text' => 'unused']; }
            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array { return str_contains(strtolower($text), 'return') ? [1.0, 0.0] : [0.0, 1.0]; }
            public function name(): string { return 'fake'; }
        });
        config(['services.ai.embedding_model' => 'test-embedding', 'services.ai.minimum_similarity' => 0.8]);
    }

    public function test_active_knowledge_is_chunked_indexed_and_retrieved(): void
    {
        $base = AiKnowledgeBase::create(['name' => 'ALAS Support']);
        $document = AiKnowledgeDocument::create(['knowledge_base_id' => $base->id, 'title' => 'Return Policy', 'content' => 'Returns are reviewed under the approved ALAS return policy.', 'status' => 'DRAFT']);
        app(KnowledgeIndexingService::class)->index($document);
        $results = app(KnowledgeRetrievalService::class)->retrieve('What is the return policy?');
        $this->assertSame('ACTIVE', $document->fresh()->status);
        $this->assertCount(1, $results);
        $this->assertSame($document->id, $results[0]['chunk']->document_id);
    }

    public function test_disabled_knowledge_is_never_retrieved(): void
    {
        $base = AiKnowledgeBase::create(['name' => 'ALAS Support']);
        $document = AiKnowledgeDocument::create(['knowledge_base_id' => $base->id, 'title' => 'Return Policy', 'content' => 'Return policy details.', 'status' => 'DRAFT']);
        app(KnowledgeIndexingService::class)->index($document);
        $document->update(['status' => 'DISABLED']);
        $this->assertSame([], app(KnowledgeRetrievalService::class)->retrieve('return policy'));
    }

    public function test_long_knowledge_is_segmented_on_sentence_boundaries(): void
    {
        $base = AiKnowledgeBase::create(['name' => 'ALAS Support']);
        $sentences = collect(range(1, 12))->map(fn ($number) => "Sentence {$number} ".str_repeat('contains useful product details ', 8).'.');
        $document = AiKnowledgeDocument::create([
            'knowledge_base_id' => $base->id,
            'title' => 'Product guide',
            'content' => $sentences->implode(' '),
            'status' => 'DRAFT',
        ]);

        app(KnowledgeIndexingService::class)->index($document);

        $chunks = $document->chunks()->orderBy('chunk_index')->pluck('content');
        $this->assertGreaterThan(1, $chunks->count());
        $chunks->each(function ($chunk) {
            $this->assertLessThanOrEqual(1400, mb_strlen($chunk));
            $this->assertMatchesRegularExpression('/\.$/u', $chunk);
        });
        $normalize = fn ($value) => preg_replace('/\s+/u', ' ', trim($value));
        $this->assertSame($normalize($sentences->implode(' ')), $normalize($chunks->implode(' ')));
    }
}
