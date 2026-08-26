<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Mail\GuestOrderTrackingMail;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.paymongo.mode' => 'live',
            'services.paymongo.live_secret_key' => 'sk_live_example',
            'services.paymongo.test_secret_key' => 'sk_test_example',
            'services.storefront.url' => 'https://shop.example.test',
            'mail.default' => 'array',
        ]);
        Mail::fake();
        Http::fake([
            'https://api.paymongo.com/v1/merchants/capabilities/payment_methods' => Http::response(['data' => ['attributes' => ['payment_methods' => ['qrph']]]]),
            'https://api.paymongo.com/v1/payment_intents' => Http::response(['data' => ['id' => 'pi_live_123', 'attributes' => ['status' => 'awaiting_payment_method']]]),
            'https://api.paymongo.com/v1/payment_methods' => Http::response(['data' => ['id' => 'pm_live_123', 'attributes' => ['type' => 'qrph']]]),
            'https://api.paymongo.com/v1/payment_intents/pi_live_123/attach' => Http::response(['data' => ['id' => 'pi_live_123', 'attributes' => [
                'status' => 'awaiting_next_action', 'next_action' => ['code' => ['image_url' => 'data:image/png;base64,qr-code', 'expires_at' => now()->addMinutes(30)->timestamp]],
            ]]]),
        ]);
    }

    public function test_checkout_prices_on_server_deducts_stock_and_can_be_tracked(): void
    {
        $product = $this->product(stock: 5);
        $key = (string) Str::uuid();

        $response = $this->withHeader('Idempotency-Key', $key)->postJson('/api/v1/storefront/checkouts', [
            'customer' => ['name' => 'Store Customer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'shipping',
            ...$this->shippingFields($product->id, 2),
            'items' => [['variant_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_centavos', 162000)
            ->assertJsonPath('data.payment_method', 'qrph')
            ->assertJsonPath('data.qr_image_url', 'data:image/png;base64,qr-code')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonMissingPath('data.customer_email');

        $this->assertSame(3, $product->inventory()->first()->current_stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => -2, 'movement_type' => 'sale']);
        Mail::assertSent(GuestOrderTrackingMail::class, fn ($mail) => $mail->hasTo('buyer@example.com') && str_contains($mail->trackingUrl, $response->json('data.tracking_token')));
        Http::assertSent(fn ($request) => $request->url() === 'https://api.paymongo.com/v1/payment_intents'
            && $request['data']['attributes']['amount'] === 162000
            && $request['data']['attributes']['payment_method_allowed'] === ['qrph']);

        $token = $response->json('data.tracking_token');
        $this->getJson("/api/v1/storefront/orders/{$token}")
            ->assertOk()
            ->assertJsonPath('data.order_number', $response->json('data.order_number'));
    }

    public function test_checkout_idempotency_does_not_duplicate_order_or_stock_deduction(): void
    {
        $product = $this->product(stock: 5);
        $key = (string) Str::uuid();
        $payload = [
            'customer' => ['name' => 'Store Customer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'meetup',
            'meetup_location' => 'ALAS Shop',
            'items' => [['variant_id' => $product->id, 'quantity' => 1]],
        ];

        $first = $this->withHeader('Idempotency-Key', $key)->postJson('/api/v1/storefront/checkouts', $payload)->assertCreated();
        $second = $this->withHeader('Idempotency-Key', $key)->postJson('/api/v1/storefront/checkouts', $payload)->assertCreated();

        $this->assertSame($first->json('data.order_number'), $second->json('data.order_number'));
        $this->assertSame(1, Order::count());
        $this->assertSame(1, StockMovement::where('movement_type', 'sale')->count());
        $this->assertSame(4, $product->inventory()->first()->current_stock);
    }

    public function test_checkout_rejects_insufficient_stock_without_partial_writes(): void
    {
        $product = $this->product(stock: 1);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/storefront/checkouts', [
            'customer' => ['name' => 'Store Customer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'shipping',
            ...$this->shippingFields($product->id, 2),
            'items' => [['variant_id' => $product->id, 'quantity' => 2]],
        ])->assertUnprocessable();

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->inventory()->first()->current_stock);
    }

    public function test_live_checkout_stops_when_qrph_capability_is_missing(): void
    {
        Cache::put('paymongo:live:payment-method-capabilities', ['card'], now()->addMinute());
        $product = $this->product(stock: 2);

        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/storefront/checkouts', [
            'customer' => ['name' => 'Store Customer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'shipping', ...$this->shippingFields($product->id, 1),
            'items' => [['variant_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(503)->assertJsonPath('code', 'QRPH_UNAVAILABLE');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $product->inventory()->first()->current_stock);
    }

    public function test_legacy_sandbox_checkout_session_remains_available_without_changing_stock(): void
    {
        config([
            'services.storefront_sandbox.enabled' => true,
            'services.storefront_sandbox.token' => 'sandbox-token',
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.test_secret_key' => 'sk_test_example',
        ]);
        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/test-session'],
                ],
            ], 200),
        ]);
        $product = $this->product(stock: 5);

        $response = $this->withHeaders([
            'Idempotency-Key' => (string) Str::uuid(),
            'X-Commerce-Mode' => 'sandbox',
            'X-Sandbox-Token' => 'sandbox-token',
            'X-Payment-Flow' => 'checkout_session',
        ])->postJson('/api/v1/storefront/checkouts', [
            'customer' => ['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'shipping',
            ...$this->shippingFields($product->id, 2),
            'items' => [['variant_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.commerce_mode', 'sandbox')
            ->assertJsonPath('data.checkout_url', 'https://checkout.paymongo.com/test-session');
        $this->assertStringStartsWith('TEST-WEB-', $response->json('data.order_number'));
        $this->assertSame(5, $product->inventory()->first()->current_stock);
        $this->assertSame(0, StockMovement::where('movement_type', 'sale')->count());
        $this->assertDatabaseHas('orders', ['commerce_mode' => 'sandbox', 'paymongo_checkout_session_id' => 'cs_test_123']);
        Http::assertSent(fn ($request) => $request['data']['attributes']['reference_number'] === $response->json('data.order_number'));
    }

    public function test_sandbox_qrph_creates_test_qr_without_changing_stock(): void
    {
        config(['services.storefront_sandbox.enabled' => true, 'services.storefront_sandbox.token' => 'sandbox-token']);
        $product = $this->product(stock: 5);

        $response = $this->withHeaders([
            'Idempotency-Key' => (string) Str::uuid(), 'X-Commerce-Mode' => 'sandbox',
            'X-Sandbox-Token' => 'sandbox-token', 'X-Payment-Flow' => 'qrph',
        ])->postJson('/api/v1/storefront/checkouts', [
            'customer' => ['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'shipping', ...$this->shippingFields($product->id, 2),
            'items' => [['variant_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.commerce_mode', 'sandbox')
            ->assertJsonPath('data.payment_method', 'qrph')
            ->assertJsonPath('data.qr_image_url', 'data:image/png;base64,qr-code');
        $this->assertSame(5, $product->inventory()->first()->current_stock);
        $this->assertSame(0, StockMovement::where('movement_type', 'sale')->count());
        Http::assertSent(fn ($request) => $request->url() === 'https://api.paymongo.com/v1/payment_intents'
            && str_starts_with($request->header('Authorization')[0], 'Basic '));
    }

    public function test_sandbox_checkout_requires_the_server_token(): void
    {
        config(['services.storefront_sandbox.enabled' => true, 'services.storefront_sandbox.token' => 'correct-token']);
        $product = $this->product(stock: 5);

        $this->withHeaders([
            'Idempotency-Key' => (string) Str::uuid(),
            'X-Commerce-Mode' => 'sandbox',
            'X-Sandbox-Token' => 'wrong-token',
        ])->postJson('/api/v1/storefront/checkouts', [
            'customer' => ['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'phone' => '09171234567'],
            'delivery_method' => 'shipping',
            ...$this->shippingFields($product->id, 1),
            'items' => [['variant_id' => $product->id, 'quantity' => 1]],
        ])->assertForbidden();

        $this->assertSame(0, Order::count());
    }

    public function test_guest_can_refresh_and_track_a_paid_checkout_without_private_details(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_example']);
        $order = Order::create([
            'order_number' => 'TEST-WEB-TRACK',
            'public_token' => (string) Str::uuid(),
            'payment_status' => 'pending',
            'paymongo_checkout_session_id' => 'cs_test_paid',
            'customer_name' => 'Guest Buyer',
            'customer_email' => 'private@example.com',
            'shipping_address' => 'Private address',
            'delivery_method' => 'shipping',
            'payment_method' => 'online',
            'total_amount' => 100,
        ]);
        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_paid' => Http::response(['data' => ['attributes' => [
                'payment_intent' => ['attributes' => ['status' => 'succeeded', 'payments' => []]],
            ]]]),
        ]);

        $this->postJson("/api/v1/storefront/orders/{$order->public_token}/refresh-payment")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonMissingPath('data.customer_email')
            ->assertJsonMissingPath('data.shipping_address');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
    }

    public function test_signed_paymongo_webhook_is_idempotent_and_confirms_matching_amount(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_example', 'services.paymongo.webhook_secret' => 'whsec_test']);
        $order = Order::create([
            'order_number' => 'TEST-WEB-HOOK', 'public_token' => (string) Str::uuid(), 'customer_name' => 'Guest',
            'payment_status' => 'pending', 'payment_method' => 'online', 'delivery_method' => 'shipping',
            'currency' => 'PHP', 'total_amount' => 100, 'paymongo_payment_intent_id' => 'pi_test_hook', 'commerce_mode' => 'sandbox',
        ]);
        $payload = json_encode(['data' => ['id' => 'evt_test_hook', 'attributes' => [
            'type' => 'payment.paid', 'livemode' => false, 'data' => ['id' => 'pay_test_hook', 'attributes' => [
                'amount' => 10000, 'currency' => 'PHP', 'status' => 'paid', 'payment_intent_id' => 'pi_test_hook', 'paid_at' => now()->timestamp,
            ]],
        ]]], JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        foreach ([1, 2] as $_) {
            $this->call('POST', '/api/v1/webhooks/paymongo', [], [], [], [
                'CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature}",
            ], $payload)->assertOk();
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'paymongo_payment_id' => 'pay_test_hook']);
        $this->assertDatabaseCount('paymongo_webhook_events', 1);
    }

    public function test_invalid_webhook_signature_does_not_change_order(): void
    {
        config(['services.paymongo.test_webhook_secret' => 'whsec_test']);
        $order = Order::create([
            'order_number' => 'TEST-WEB-FORGED', 'public_token' => (string) Str::uuid(), 'customer_name' => 'Guest',
            'payment_status' => 'pending', 'payment_method' => 'qrph', 'delivery_method' => 'shipping',
            'currency' => 'PHP', 'total_amount' => 100, 'commerce_mode' => 'sandbox',
        ]);
        $payload = json_encode(['data' => ['id' => 'evt_forged', 'attributes' => ['type' => 'payment.paid', 'livemode' => false]]]);

        $this->call('POST', '/api/v1/webhooks/paymongo', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => 't='.now()->timestamp.',te=forged',
        ], $payload)->assertUnauthorized();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertDatabaseCount('paymongo_webhook_events', 0);
    }

    public function test_qr_expiration_keeps_order_unpaid_and_allows_regeneration(): void
    {
        config(['services.paymongo.live_webhook_secret' => 'whsec_live']);
        $order = Order::create([
            'order_number' => 'WEB-EXPIRED', 'public_token' => (string) Str::uuid(), 'customer_name' => 'Guest',
            'payment_status' => 'pending', 'payment_method' => 'qrph', 'delivery_method' => 'shipping',
            'currency' => 'PHP', 'total_amount' => 100, 'commerce_mode' => 'live',
            'paymongo_payment_intent_id' => 'pi_live_123', 'paymongo_payment_method_id' => 'pm_live_expired',
            'paymongo_qr_image_url' => 'old-qr', 'paymongo_qr_expires_at' => now()->subMinute(),
        ]);
        $payload = json_encode(['data' => ['id' => 'evt_expired', 'attributes' => [
            'type' => 'qrph.expired', 'livemode' => true, 'data' => ['id' => 'pm_live_expired', 'attributes' => []],
        ]]], JSON_UNESCAPED_SLASHES);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_live');

        $this->call('POST', '/api/v1/webhooks/paymongo', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},li={$signature}",
        ], $payload)->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending', 'payment_error_code' => 'qr_expired', 'paymongo_qr_image_url' => null]);

        Http::fake([
            'https://api.paymongo.com/v1/merchants/capabilities/payment_methods' => Http::response(['data' => ['attributes' => ['payment_methods' => ['qrph']]]]),
            'https://api.paymongo.com/v1/payment_methods' => Http::response(['data' => ['id' => 'pm_live_new']]),
            'https://api.paymongo.com/v1/payment_intents/pi_live_123/attach' => Http::response(['data' => ['attributes' => ['next_action' => ['code' => ['image_url' => 'new-qr']]]]]),
        ]);
        $this->postJson("/api/v1/storefront/orders/{$order->public_token}/regenerate-qr")
            ->assertOk()->assertJsonPath('data.qr_image_url', 'data:image/png;base64,qr-code');
        $this->assertSame(1, $order->fresh()->paymongo_payment_attempt);
    }

    private function product(int $stock)
    {
        $manager = User::factory()->create(['role' => 'manager']);

        return CreateProductAction::execute([
            'product_name' => 'ALAS Heavy Tee Black S',
            'storefront_name' => 'ALAS Heavy Tee',
            'storefront_slug' => 'alas-heavy-tee',
            'sku' => 'ALAS-HEAVY-BLK-S',
            'category' => 'T-Shirts',
            'color' => 'Black',
            'size' => 'S',
            'selling_price' => 750,
            'cost_price' => 300,
            'initial_stock' => $stock,
        ], $manager);
    }

    private function shippingFields(int $productId, int $quantity): array
    {
        $session = (string) Str::uuid();
        $address = ['country' => 'Philippines', 'region' => 'National Capital Region (NCR)', 'region_code' => '1300000000', 'province' => 'Metro Manila', 'city' => 'City of Manila', 'city_code' => '1380600000', 'barangay' => 'Barangay 1', 'barangay_code' => '1380601001', 'postal_code' => '1000', 'street_address' => '123 Test Street'];
        $quote = $this->postJson('/api/v1/storefront/shipping/quotes', ['session_id' => $session, 'address' => $address, 'items' => [['variant_id' => $productId, 'quantity' => $quantity]]])->assertOk()->json('data.quotes.0');

        return ['shipping_address' => '123 Test Street, Manila, Philippines', 'delivery_address' => $address, 'shipping_quote_id' => $quote['quote_id'], 'shipping_session_id' => $session];
    }
}
