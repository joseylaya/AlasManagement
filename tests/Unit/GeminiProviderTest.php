<?php

namespace Tests\Unit;

use App\Services\Ai\GeminiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_it_uses_the_next_model_when_the_first_reaches_its_limit(): void
    {
        Cache::flush();
        config(['services.ai.api_key' => 'test-key', 'services.ai.models' => 'model-one,model-two', 'services.ai.timeout' => 1]);
        Http::fakeSequence()
            ->push(['error' => ['message' => 'quota']], 429, ['Retry-After' => '60'])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Hello from fallback']]]]], 'usageMetadata' => ['promptTokenCount' => 4, 'candidatesTokenCount' => 3]], 200);

        $result = app(GeminiProvider::class)->generate('System', [['role' => 'user', 'content' => 'Hello']], 100);

        $this->assertSame('model-two', $result['model']);
        $this->assertSame('Hello from fallback', $result['text']);
        Http::assertSentCount(2);
    }
}
