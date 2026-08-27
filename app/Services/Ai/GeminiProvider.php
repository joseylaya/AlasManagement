<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AiProvider
{
    public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
    {
        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents' => array_map(fn ($message) => ['role' => $message['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $message['content']]]], $messages),
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => min(250, $maxOutputTokens),
                'thinkingConfig' => ['thinkingLevel' => 'LOW'],
            ],
        ];
        $models = $this->models();
        $available = array_values(array_filter($models, fn ($model) => ! Cache::has($this->cooldownKey($model))));
        if ($available === []) {
            throw new RuntimeException('All configured Gemini chat models are in cooldown.');
        }
        $errors = [];

        foreach ($available as $model) {
            try {
                $httpResponse = $this->client()->post($this->endpoint($model, 'generateContent'), $payload);
            } catch (ConnectionException $exception) {
                $this->coolDown($model, 30, 'connection failure');
                $errors[] = "{$model}: connection failure";

                continue;
            }

            if ($httpResponse->successful()) {
                $response = $httpResponse->json();
                $text = collect(data_get($response, 'candidates.0.content.parts', []))->reject(fn ($part) => data_get($part, 'thought') === true)->pluck('text')->filter()->implode('');
                if (is_string($text) && trim($text) !== '') {
                    return ['text' => trim($text), 'model' => $model, 'prompt_tokens' => data_get($response, 'usageMetadata.promptTokenCount'), 'completion_tokens' => data_get($response, 'usageMetadata.candidatesTokenCount')];
                }
                $this->coolDown($model, 15, 'empty response');
                $errors[] = "{$model}: empty response";

                continue;
            }

            if (in_array($httpResponse->status(), [429, 500, 502, 503, 504], true)) {
                $retryAfter = max(30, min(300, (int) $httpResponse->header('Retry-After', $httpResponse->status() === 429 ? 60 : 30)));
                $this->coolDown($model, $retryAfter, 'HTTP '.$httpResponse->status());
                $errors[] = "{$model}: HTTP {$httpResponse->status()}";

                continue;
            }

            if ($httpResponse->status() === 404) {
                $this->coolDown($model, 3600, 'model unavailable');
                $errors[] = "{$model}: unavailable";

                continue;
            }

            $httpResponse->throw();
        }

        throw new RuntimeException('All configured Gemini chat models are temporarily unavailable. '.implode('; ', $errors));
    }

    public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $model = config('services.ai.embedding_model', 'gemini-embedding-2');
        $response = $this->client()->post($this->endpoint($model, 'embedContent'), [
            'model' => "models/{$model}",
            'content' => ['parts' => [['text' => $text]]],
            'taskType' => $taskType,
            'outputDimensionality' => (int) config('services.ai.embedding_dimension', 1536),
        ])->throw()->json();
        $values = data_get($response, 'embedding.values');
        if (! is_array($values) || $values === []) {
            throw new RuntimeException('Gemini returned no embedding.');
        }

        return array_map('floatval', $values);
    }

    public function name(): string
    {
        return 'gemini';
    }

    private function client()
    {
        $key = config('services.ai.api_key');
        if (! filled($key)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        return Http::acceptJson()->withHeaders(['x-goog-api-key' => $key])->connectTimeout(5)->timeout((int) config('services.ai.timeout', 20));
    }

    private function endpoint(string $model, string $method): string
    {
        return rtrim(config('services.ai.api_url', 'https://generativelanguage.googleapis.com/v1beta'), '/')."/models/{$model}:{$method}";
    }

    private function models(): array
    {
        $configured = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) config('services.ai.models'))))));
        if ($configured !== []) {
            return $configured;
        }

        return array_values(array_unique(array_filter([config('services.ai.model'), config('services.ai.fallback_model')])));
    }

    private function cooldownKey(string $model): string
    {
        return 'ai:gemini:model-cooldown:'.sha1($model);
    }

    private function coolDown(string $model, int $seconds, string $reason): void
    {
        $stateKey = $this->cooldownKey($model).':state';
        $state = Cache::get($stateKey, ['failure_count' => 0]);
        Cache::put($stateKey, [
            'model' => $model,
            'cooldown_until' => now()->addSeconds($seconds)->toIso8601String(),
            'failure_count' => ((int) ($state['failure_count'] ?? 0)) + 1,
            'last_failure_reason' => $reason,
        ], now()->addDay());
        Cache::put($this->cooldownKey($model), true, now()->addSeconds($seconds));
    }
}
