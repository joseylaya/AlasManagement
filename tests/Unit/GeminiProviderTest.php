<?php

namespace Tests\Unit;

use App\Services\Ai\GeminiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_chat_generation_uses_the_configured_response_and_thinking_limits(): void
    {
        config()->set('services.ai.api_key', 'test-key');
        config()->set('services.ai.models', 'gemini-3.7-flash');
        Cache::flush();
        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Hello!']]]]],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 2],
            ]),
        ]);

        app(GeminiProvider::class)->generate('System prompt', [['role' => 'user', 'content' => 'Hi']], 500);

        Http::assertSent(function ($request) {
            return data_get($request->data(), 'generationConfig.maxOutputTokens') === 250
                && data_get($request->data(), 'generationConfig.temperature') === 0.5
                && data_get($request->data(), 'generationConfig.thinkingConfig.thinkingLevel') === 'LOW';
        });
    }

    public function test_transient_failures_cool_models_down_and_fall_through_in_one_turn(): void
    {
        config()->set('services.ai.api_key', 'test-key');
        config()->set('services.ai.models', 'model-one,model-two,model-three');
        Cache::flush();
        Http::fakeSequence()
            ->push([], 429, ['Retry-After' => '60'])
            ->push([], 503)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Fallback worked']]]]]], 200);

        $result = app(GeminiProvider::class)->generate('System', [['role' => 'user', 'content' => 'Hi']], 250);

        $this->assertSame('model-three', $result['model']);
        $this->assertSame(3, count(Http::recorded()));
        $state = Cache::get('ai:gemini:model-cooldown:'.sha1('model-one').':state');
        $this->assertSame(1, $state['failure_count']);
        $this->assertSame('HTTP 429', $state['last_failure_reason']);
    }

    public function test_models_still_in_cooldown_are_not_retried(): void
    {
        config()->set('services.ai.api_key', 'test-key');
        config()->set('services.ai.models', 'cooling,available');
        Cache::flush();
        Cache::put('ai:gemini:model-cooldown:'.sha1('cooling'), true, now()->addMinute());
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Okay']]]]]])]);

        $result = app(GeminiProvider::class)->generate('System', [['role' => 'user', 'content' => 'Hi']], 250);

        $this->assertSame('available', $result['model']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/models/available:generateContent'));
    }
}
