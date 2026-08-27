<?php

namespace Tests\Feature;

use App\Actions\Support\CreateSupportConversationAction;
use App\Actions\Support\SendAdminSupportMessageAction;
use App\Actions\Support\SendCustomerSupportMessageAction;
use App\Actions\Support\TakeOverSupportConversationAction;
use App\Actions\CreateProductAction;
use App\Contracts\AiProvider;
use App\Enums\SupportConversationMode;
use App\Jobs\GenerateSupportAiResponse;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiSupportConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_and_reopen_only_its_opaque_conversation(): void
    {
        $created = $this->postJson('/api/v1/support/conversations', ['display_name' => 'Guest'])->assertCreated();
        $id = $created->json('data.id');
        $token = $created->json('support_token');

        $this->getJson("/api/v1/support/conversations/{$id}")->assertForbidden();
        $this->withToken($token)->getJson("/api/v1/support/conversations/{$id}")
            ->assertOk()->assertJsonPath('data.id', $id);
    }

    public function test_customer_message_is_idempotent_and_queues_one_ai_run(): void
    {
        Queue::fake();
        $result = app(CreateSupportConversationAction::class)->execute([]);
        $conversation = $result['conversation'];

        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'What is your return policy?', 'client-1');
        app(SendCustomerSupportMessageAction::class)->execute($conversation, 'What is your return policy?', 'client-1');

        $this->assertSame(1, SupportMessage::where('sender_type', 'CUSTOMER')->count());
        Queue::assertPushed(GenerateSupportAiResponse::class, 1);
    }

    public function test_conversation_messages_are_returned_in_stable_chronological_order(): void
    {
        $result = app(CreateSupportConversationAction::class)->execute([]);
        $conversation = $result['conversation'];
        $conversation->messages()->delete();
        $timestamp = now()->startOfSecond();

        foreach ([
            ['00000000-0000-7000-8000-000000000001', 'CUSTOMER', 'First question', 0],
            ['00000000-0000-7000-8000-000000000002', 'AI', 'First answer', -8],
            ['00000000-0000-7000-8000-000000000003', 'CUSTOMER', 'Second question', 0],
            ['00000000-0000-7000-8000-000000000004', 'AI', 'Second answer', -8],
        ] as [$id, $sender, $content, $hourOffset]) {
            SupportMessage::forceCreate([
                'id' => $id,
                'conversation_id' => $conversation->id,
                'sender_type' => $sender,
                'content_type' => 'TEXT',
                'content' => $content,
                'delivery_status' => 'SENT',
                'created_at' => $timestamp->copy()->addHours($hourOffset),
                'updated_at' => $timestamp->copy()->addHours($hourOffset),
            ]);
        }

        $this->withToken($result['token'])
            ->getJson("/api/v1/support/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.messages.0.content', 'First question')
            ->assertJsonPath('data.messages.1.content', 'First answer')
            ->assertJsonPath('data.messages.2.content', 'Second question')
            ->assertJsonPath('data.messages.3.content', 'Second answer');
    }

    public function test_manual_admin_reply_atomically_takes_over_before_sending(): void
    {
        $admin = User::factory()->create(['role' => 'staff']);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];

        app(SendAdminSupportMessageAction::class)->execute($conversation, $admin, 'I can help with that.');

        $this->assertSame(SupportConversationMode::HUMAN_ACTIVE, $conversation->fresh()->mode);
        $this->assertSame($admin->id, $conversation->fresh()->assigned_admin_id);
        $this->assertDatabaseHas('support_events', ['conversation_id' => $conversation->id, 'event_type' => 'HUMAN_TAKEOVER']);
        $this->assertDatabaseHas('support_messages', ['conversation_id' => $conversation->id, 'sender_type' => 'ADMIN']);
    }

    public function test_takeover_is_audited_and_disables_customer_facing_ai_mode(): void
    {
        $admin = User::factory()->create(['role' => 'manager']);
        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        app(TakeOverSupportConversationAction::class)->execute($conversation, $admin);

        $this->assertSame(SupportConversationMode::HUMAN_ACTIVE, $conversation->fresh()->mode);
        $this->assertDatabaseHas('support_events', ['event_type' => 'HUMAN_TAKEOVER', 'actor_id' => $admin->id]);
    }

    public function test_ai_result_is_discarded_when_human_takes_over_during_generation(): void
    {
        $admin = User::factory()->create(['role' => 'manager']);
        $product = CreateProductAction::execute(['product_name' => 'ALAS Tee', 'storefront_name' => 'ALAS Tee', 'sku' => 'AI-RACE-TEE', 'category' => 'T-Shirts', 'selling_price' => 700, 'cost_price' => 300, 'initial_stock' => 2], $admin);
        $result = app(CreateSupportConversationAction::class)->execute(['context' => ['variant_id' => $product->id]]);
        Queue::fake();
        $trigger = app(SendCustomerSupportMessageAction::class)->execute($result['conversation'], 'Is this available?', 'race-1');

        $this->app->instance(AiProvider::class, new class($result['conversation'], $admin) implements AiProvider {
            public function __construct(private $conversation, private $admin) {}
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                app(TakeOverSupportConversationAction::class)->execute($this->conversation, $this->admin);
                return ['text' => 'This must never reach the customer.', 'prompt_tokens' => 1, 'completion_tokens' => 1];
            }
            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array { return [1.0, 0.0]; }
            public function name(): string { return 'fake'; }
        });

        app(\App\Services\Ai\AiResponseService::class)->respondTo($trigger);

        $this->assertDatabaseMissing('support_messages', ['content' => 'This must never reach the customer.']);
        $this->assertDatabaseHas('ai_runs', ['trigger_message_id' => $trigger->id, 'status' => 'DISCARDED_TAKEOVER']);
    }

    public function test_every_existing_admin_role_can_open_the_support_inbox(): void
    {
        foreach (['owner', 'manager', 'staff'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/ai-support')->assertOk()->assertSee('AI Support');
        }
    }

    public function test_ai_can_converse_naturally_without_knowledge_but_does_not_invent_business_facts(): void
    {
        Queue::fake();
        $this->app->instance(AiProvider::class, new class implements AiProvider {
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array { return ['text' => 'Hi! I’m here and happy to help. What can I help you with today?', 'prompt_tokens' => 10, 'completion_tokens' => 12]; }
            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array { return [1.0, 0.0]; }
            public function name(): string { return 'fake'; }
        });

        $greetingConversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        $greeting = app(SendCustomerSupportMessageAction::class)->execute($greetingConversation, 'Hello, kumusta?', 'natural-1');
        app(\App\Services\Ai\AiResponseService::class)->respondTo($greeting);
        $this->assertSame(
            ['Hi!', 'I’m here and happy to help.', 'What can I help you with today?'],
            SupportMessage::where('conversation_id', $greetingConversation->id)->where('sender_type', 'AI')->orderBy('id')->pluck('content')->all()
        );

        $businessConversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        $businessQuestion = app(SendCustomerSupportMessageAction::class)->execute($businessConversation, 'What is your return policy?', 'natural-2');
        app(\App\Services\Ai\AiResponseService::class)->respondTo($businessQuestion);
        $this->assertSame(SupportConversationMode::AI_PAUSED, $businessConversation->fresh()->mode);
        $this->assertDatabaseHas('support_events', ['conversation_id' => $businessConversation->id, 'event_type' => 'AI_ESCALATED']);
    }

    public function test_one_ai_call_is_persisted_as_ordered_sentence_bubbles(): void
    {
        Queue::fake();
        $provider = new class implements AiProvider {
            public int $calls = 0;
            public function generate(string $systemInstruction, array $messages, int $maxOutputTokens): array
            {
                $this->calls++;
                return ['text' => 'Sure boss! Available ni in black. Ganahan ka regular or oversized?', 'prompt_tokens' => 10, 'completion_tokens' => 14];
            }
            public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array { return [1.0, 0.0]; }
            public function name(): string { return 'fake'; }
        };
        $this->app->instance(AiProvider::class, $provider);

        $conversation = app(CreateSupportConversationAction::class)->execute([])['conversation'];
        $trigger = app(SendCustomerSupportMessageAction::class)->execute($conversation, 'Naa moy black boss?', 'segments-1');
        app(\App\Services\Ai\AiResponseService::class)->respondTo($trigger);

        $this->assertSame(1, $provider->calls);
        $this->assertSame(
            ['Sure boss!', 'Available ni in black.', 'Ganahan ka regular or oversized?'],
            SupportMessage::where('conversation_id', $conversation->id)->where('sender_type', 'AI')->orderBy('id')->pluck('content')->all()
        );
        $this->assertDatabaseCount('ai_runs', 1);
    }
}
