<?php

namespace Tests\Feature;

use App\Actions\Support\CreateSupportConversationAction;
use App\Actions\Support\SendCustomerSupportMessageAction;
use App\Actions\Support\TakeOverSupportConversationAction;
use App\Contracts\AiProvider;
use App\Jobs\GenerateSupportAiResponse;
use App\Jobs\PublishSupportAiResponse;
use App\Models\SupportAiJob;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\Ai\AiResponseService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiChatQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_rapid_messages_reset_one_three_second_debounce_batch(): void
    {
        Queue::fake();
        $start = CarbonImmutable::parse('2026-08-27 10:00:00');
        $this->travelTo($start);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];

        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'boss naa koy pangutana', 'batch-1');
        $this->travelTo($start->addSecond());
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'naa moy black XL?', 'batch-2');
        $this->travelTo($start->addSeconds(2));
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'pila pud?', 'batch-3');

        $batch = SupportAiJob::sole();
        $this->assertSame('DEBOUNCING', $batch->status);
        $this->assertSame('batch-1', SupportMessage::find($batch->first_message_id)->client_message_id);
        $this->assertSame('batch-3', SupportMessage::find($batch->last_message_id)->client_message_id);
        $this->assertTrue($batch->ready_at->equalTo($start->addSeconds(5)));
        Queue::assertPushed(GenerateSupportAiResponse::class, 1);
    }

    public function test_continuous_typing_never_extends_batch_beyond_maximum_wait(): void
    {
        Queue::fake();
        $start = CarbonImmutable::parse('2026-08-27 10:00:00');
        $this->travelTo($start);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        foreach ([0, 2, 4, 6, 8] as $index => $seconds) {
            $this->travelTo($start->addSeconds($seconds));
            app(SendCustomerSupportMessageAction::class)->execute($conversation, 'part '.($index + 1), 'max-'.$index);
        }

        $this->assertTrue(SupportAiJob::sole()->ready_at->equalTo($start->addSeconds(8)));
        Queue::assertPushed(GenerateSupportAiResponse::class, 1);
    }

    public function test_one_llm_call_receives_the_combined_turn_and_preserves_all_raw_messages(): void
    {
        Queue::fake();
        $provider = new class implements AiProvider
        {
            public int $calls = 0;

            public array $messages = [];

            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->calls++;
                $this->messages = $messages;

                return ['text' => 'Sige boss!', 'model' => 'fake', 'prompt_tokens' => 20, 'completion_tokens' => 3];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        };
        $this->app->instance(AiProvider::class, $provider);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        foreach (['boss naa koy pangutana', 'kumusta?', 'patabang ko please'] as $index => $content) {
            $last = app(SendCustomerSupportMessageAction::class)->execute($conversation, $content, 'combined-'.$index);
        }

        app(AiResponseService::class)->respondTo($last);

        $this->assertSame(1, $provider->calls);
        $prompt = collect($provider->messages)->pluck('content')->implode("\n");
        $this->assertStringContainsString("boss naa koy pangutana\nkumusta?\npatabang ko please", $prompt);
        $this->assertSame(3, SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'CUSTOMER')->count());
    }

    public function test_duplicates_are_saved_but_removed_from_the_llm_batch(): void
    {
        Queue::fake();
        $provider = new class implements AiProvider
        {
            public string $prompt = '';

            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->prompt = collect($messages)->pluck('content')->implode("\n");

                return ['text' => 'Hello boss!', 'model' => 'fake', 'prompt_tokens' => 10, 'completion_tokens' => 2];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        };
        $this->app->instance(AiProvider::class, $provider);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        foreach (['boss', 'boss', 'hello'] as $index => $content) {
            $last = app(SendCustomerSupportMessageAction::class)->execute($conversation, $content, 'duplicate-'.$index);
        }

        app(AiResponseService::class)->respondTo($last);

        $this->assertSame(3, SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'CUSTOMER')->count());
        $batched = str($provider->prompt)->after('CUSTOMER MESSAGES (one batched turn):')->toString();
        $this->assertSame(1, substr_count(mb_strtolower($batched), 'boss'));
    }

    public function test_rate_limit_preserves_messages_and_uses_no_llm_call(): void
    {
        Queue::fake();
        $provider = new class implements AiProvider
        {
            public int $calls = 0;

            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->calls++;

                return [];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        };
        $this->app->instance(AiProvider::class, $provider);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        foreach (range(1, 9) as $index) {
            $last = app(SendCustomerSupportMessageAction::class)->execute($conversation, 'message '.$index, 'rate-'.$index);
        }

        app(AiResponseService::class)->respondTo($last);

        $this->assertSame(0, $provider->calls);
        $this->assertSame(9, SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'CUSTOMER')->count());
        $this->assertSame('RATE_LIMITED', SupportAiJob::sole()->status);
        $this->assertDatabaseHas('support_messages', ['conversation_id' => $conversation->id, 'sender_type' => 'SYSTEM']);
    }

    public function test_punctuation_spam_is_preserved_but_never_sent_to_the_llm(): void
    {
        Queue::fake();
        config()->set('ai_chat.rate_short_limit', 20);
        $provider = new class implements AiProvider
        {
            public int $calls = 0;

            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->calls++;

                return [];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        };
        $this->app->instance(AiProvider::class, $provider);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        foreach (['?', '??', '!', '!!!', '...'] as $index => $content) {
            $last = app(SendCustomerSupportMessageAction::class)->execute($conversation, $content, 'spam-'.$index);
        }

        app(AiResponseService::class)->respondTo($last);

        $this->assertSame(0, $provider->calls);
        $this->assertSame(5, SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'CUSTOMER')->count());
        $this->assertSame('RATE_LIMITED', SupportAiJob::sole()->status);
    }

    public function test_generation_stages_a_delayed_publish_instead_of_holding_the_llm_worker(): void
    {
        Queue::fake();
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                return ['text' => 'Short reply.', 'model' => 'fake', 'prompt_tokens' => 4, 'completion_tokens' => 2];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        });
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        $message = app(SendCustomerSupportMessageAction::class)->execute($conversation, 'hello', 'delay-1');
        $batch = SupportAiJob::sole();
        $batch->update(['status' => 'PROCESSING', 'started_at' => now()]);

        app(AiResponseService::class)->generate($batch->fresh());

        $this->assertSame('TYPING_DELAY', $batch->fresh()->status);
        $this->assertDatabaseMissing('support_messages', ['conversation_id' => $conversation->id, 'sender_type' => 'AI']);
        Queue::assertPushed(PublishSupportAiResponse::class, 1);
        $this->assertNotNull($message);
    }

    public function test_takeover_cancels_every_pending_batch(): void
    {
        Queue::fake();
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'hello', 'cancel-1');

        app(TakeOverSupportConversationAction::class)->execute($conversation, User::factory()->create(['role' => 'manager']));

        $this->assertSame('CANCELLED', SupportAiJob::sole()->status);
        $this->assertSame('HUMAN_TAKEOVER', SupportAiJob::sole()->error_code);
    }

    public function test_configured_message_ceiling_blocks_oversized_input_before_persistence(): void
    {
        config()->set('ai_chat.max_message_chars', 20);
        $result = app(CreateSupportConversationAction::class)->execute([]);

        $this->withToken($result['token'])->postJson("/api/v1/support/conversations/{$result['conversation']->id}/messages", [
            'content' => str_repeat('x', 21),
            'client_message_id' => 'too-long',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('support_messages', ['client_message_id' => 'too-long']);
    }

    public function test_a_later_turn_waits_until_the_earlier_turn_finishes(): void
    {
        Queue::fake();
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'first', 'fifo-1');
        $first = SupportAiJob::sole();
        $first->update(['status' => 'TYPING_DELAY']);
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'second', 'fifo-2');
        $second = SupportAiJob::whereKeyNot($first->id)->sole();
        $second->update(['ready_at' => now()->subSecond()]);

        $transport = (new GenerateSupportAiResponse($second->id))->withFakeQueueInteractions();
        $transport->handle(app(AiResponseService::class));

        $transport->assertReleased(1);
        $this->assertSame('QUEUED', $second->fresh()->status);
        $this->assertSame('TYPING_DELAY', $first->fresh()->status);
    }

    public function test_stale_active_job_is_failed_so_it_cannot_block_the_queue_forever(): void
    {
        Queue::fake();
        config()->set('ai_chat.stale_job_seconds', 60);
        config()->set('ai_chat.conversation_concurrency', 0);

        $oldConversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($oldConversation, 'old', 'stale-1');
        $stale = SupportAiJob::sole();
        $stale->timestamps = false;
        $stale->forceFill(['updated_at' => now()->subMinutes(2)])->saveQuietly();

        $newConversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($newConversation, 'new', 'stale-2');
        $current = SupportAiJob::whereKeyNot($stale->id)->sole();
        $current->update(['ready_at' => now()->subSecond()]);

        (new GenerateSupportAiResponse($current->id))->withFakeQueueInteractions()->handle(app(AiResponseService::class));

        $this->assertSame('FAILED', $stale->fresh()->status);
        $this->assertSame('STALE_JOB_RECOVERED', $stale->fresh()->error_code);
    }

    public function test_global_llm_limit_keeps_ready_work_queued_until_a_slot_is_free(): void
    {
        Queue::fake();
        config()->set('ai_chat.global_concurrency', 1);
        Cache::flush();
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'hello', 'global-1');
        $batch = SupportAiJob::sole();
        $batch->update(['ready_at' => now()->subSecond()]);
        $occupied = Cache::lock('ai-chat:global-slot:1', 30);
        $this->assertTrue($occupied->get());

        try {
            $transport = (new GenerateSupportAiResponse($batch->id))->withFakeQueueInteractions();
            $transport->handle(app(AiResponseService::class));
            $transport->assertReleased(1);
            $this->assertSame('QUEUED', $batch->fresh()->status);
            $this->assertDatabaseCount('ai_runs', 0);
        } finally {
            $occupied->release();
        }
    }

    public function test_queue_worker_generates_once_and_publication_finishes_the_same_batch(): void
    {
        Queue::fake();
        config()->set('ai_chat.typing_base_ms', 0);
        config()->set('ai_chat.typing_per_character_ms', 0);
        config()->set('ai_chat.typing_max_ms', 0);
        $provider = new class implements AiProvider
        {
            public int $calls = 0;

            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->calls++;

                return ['text' => 'One reply.', 'model' => 'fake-model', 'prompt_tokens' => 5, 'completion_tokens' => 2];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        };
        $this->app->instance(AiProvider::class, $provider);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'hello', 'worker-1');
        $batch = SupportAiJob::sole();
        $batch->update(['ready_at' => now()->subSecond()]);

        (new GenerateSupportAiResponse($batch->id))->withFakeQueueInteractions()->handle(app(AiResponseService::class));
        $this->assertSame('TYPING_DELAY', $batch->fresh()->status);
        $this->assertSame(1, $provider->calls);

        (new PublishSupportAiResponse($batch->id))->handle(app(AiResponseService::class));
        $this->assertSame('COMPLETED', $batch->fresh()->status);
        $this->assertDatabaseHas('support_messages', ['conversation_id' => $conversation->id, 'sender_type' => 'AI', 'content' => 'One reply.']);
        $this->assertDatabaseHas('ai_runs', ['trigger_message_id' => $batch->last_message_id, 'status' => 'COMPLETED', 'model' => 'fake-model']);
    }

    public function test_hard_context_budget_trims_input_before_the_provider_call(): void
    {
        Queue::fake();
        config()->set('ai_chat.target_input_tokens', 250);
        config()->set('ai_chat.hard_input_tokens', 300);
        $provider = new class implements AiProvider
        {
            public int $inputBytes = 0;

            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->inputBytes = strlen(json_encode($messages));

                return ['text' => 'Got it.', 'model' => 'fake', 'prompt_tokens' => 250, 'completion_tokens' => 2];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        };
        $this->app->instance(AiProvider::class, $provider);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        $message = app(SendCustomerSupportMessageAction::class)->execute($conversation, str_repeat('word ', 400), 'context-1');

        app(AiResponseService::class)->respondTo($message);

        $this->assertLessThanOrEqual(1200, $provider->inputBytes);
    }

    public function test_regex_sentences_publish_one_at_a_time_with_the_batch_pending_between_segments(): void
    {
        Queue::fake();
        config()->set('ai_chat.typing_base_ms', 0);
        config()->set('ai_chat.typing_per_character_ms', 0);
        config()->set('ai_chat.typing_max_ms', 0);
        config()->set('ai_chat.segment_delay_ms', 2000);
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                return ['text' => 'First sentence. Second sentence! Third sentence?', 'model' => 'fake', 'prompt_tokens' => 8, 'completion_tokens' => 8];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        });
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'hello', 'segments-delayed-1');
        $batch = SupportAiJob::sole();
        $batch->update(['status' => 'PROCESSING', 'started_at' => now()]);
        app(AiResponseService::class)->generate($batch->fresh());

        (new PublishSupportAiResponse($batch->id))->handle(app(AiResponseService::class));
        $this->assertSame(['First sentence.'], SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'AI')->pluck('content')->all());
        $this->assertSame('TYPING_DELAY', $batch->fresh()->status);
        $this->assertSame(1, $batch->fresh()->published_segment_count);
        $nextPublication = Queue::pushed(PublishSupportAiResponse::class)->last();
        $this->assertNotNull($nextPublication?->delay);
        $this->assertEqualsWithDelta(2.0, now()->floatDiffInSeconds($nextPublication->delay, false), 0.1);

        (new PublishSupportAiResponse($batch->id))->handle(app(AiResponseService::class));
        $this->assertSame(['First sentence.', 'Second sentence!'], SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'AI')->orderBy('id')->pluck('content')->all());
        $this->assertSame('TYPING_DELAY', $batch->fresh()->status);

        (new PublishSupportAiResponse($batch->id))->handle(app(AiResponseService::class));
        $this->assertSame(['First sentence.', 'Second sentence!', 'Third sentence?'], SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'AI')->orderBy('id')->pluck('content')->all());
        $this->assertSame('COMPLETED', $batch->fresh()->status);
        $this->assertSame(3, $batch->fresh()->published_segment_count);
    }

    public function test_takeover_after_first_segment_prevents_remaining_segments(): void
    {
        Queue::fake();
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                return ['text' => 'Visible first. Must not publish second.', 'model' => 'fake', 'prompt_tokens' => 6, 'completion_tokens' => 6];
            }

            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
            {
                return [1.0, 0.0];
            }

            public function name(): string
            {
                return 'fake';
            }
        });
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'hello', 'segment-takeover-1');
        $batch = SupportAiJob::sole();
        $batch->update(['status' => 'PROCESSING', 'started_at' => now()]);
        app(AiResponseService::class)->generate($batch->fresh());
        (new PublishSupportAiResponse($batch->id))->handle(app(AiResponseService::class));

        app(TakeOverSupportConversationAction::class)->execute($conversation, User::factory()->create(['role' => 'manager']));
        (new PublishSupportAiResponse($batch->id))->handle(app(AiResponseService::class));

        $this->assertSame(['Visible first.'], SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'AI')->pluck('content')->all());
        $this->assertSame('CANCELLED', $batch->fresh()->status);
    }
}
