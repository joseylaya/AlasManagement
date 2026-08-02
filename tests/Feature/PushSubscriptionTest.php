<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private array $subscription = [
        'endpoint' => 'https://push.example.test/subscription/abc',
        'keys' => [
            'p256dh' => 'test-public-key',
            'auth' => 'test-auth-token',
        ],
        'contentEncoding' => 'aes128gcm',
    ];

    public function test_authenticated_user_can_save_a_browser_push_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('push-subscriptions.store'), $this->subscription)
            ->assertOk()
            ->assertJsonPath('message', 'Push notifications enabled.');

        $this->assertDatabaseHas(PushSubscription::class, [
            'user_id' => $user->id,
            'endpoint' => $this->subscription['endpoint'],
        ]);
    }

    public function test_user_can_remove_only_their_own_subscription(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $subscription = PushSubscription::create([
            'user_id' => $other->id,
            'endpoint' => $this->subscription['endpoint'],
            'public_key' => 'test-public-key',
            'auth_token' => 'test-auth-token',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($owner)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $subscription->endpoint])
            ->assertOk();

        $this->assertDatabaseHas(PushSubscription::class, ['id' => $subscription->id]);
    }
}
