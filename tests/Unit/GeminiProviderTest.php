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
}
