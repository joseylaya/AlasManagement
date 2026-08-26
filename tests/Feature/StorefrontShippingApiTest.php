<?php

namespace Tests\Feature;

use App\Actions\CreateProductAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontShippingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.paymongo.mode' => 'live', 'services.paymongo.live_secret_key' => 'sk_live_example']);
        Http::fake([
            'https://api.paymongo.com/v1/merchants/capabilities/payment_methods' => Http::response(['data' => ['attributes' => ['payment_methods' => ['qrph']]]]),
            'https://api.paymongo.com/v1/payment_intents' => Http::response(['data' => ['id' => 'pi_shipping', 'attributes' => ['status' => 'awaiting_payment_method']]]),
            'https://api.paymongo.com/v1/payment_methods' => Http::response(['data' => ['id' => 'pm_shipping']]),
            'https://api.paymongo.com/v1/payment_intents/pi_shipping/attach' => Http::response(['data' => ['attributes' => ['next_action' => ['code' => ['image_url' => 'shipping-qr']]]]]),
        ]);
    }

    public function test_cebu_and_mandaue_offer_jnt_and_maxim_while_manila_only_offers_jnt(): void
    {
        $product = $this->product();
        foreach (['Cebu City', 'Mandaue'] as $city) {
            $quotes = $this->quote($product->id, $this->address('Cebu', $city))->assertOk()->json('data.quotes');
            $this->assertEqualsCanonicalizing(['jnt', 'maxim'], collect($quotes)->where('available', true)->pluck('provider')->all());
        }
        $quotes = $this->quote($product->id, $this->address('Metro Manila', 'Manila'))->assertOk()->json('data.quotes');
        $this->assertSame(['jnt'], collect($quotes)->where('available', true)->pluck('provider')->all());
    }

    public function test_cart_change_invalidates_a_quote(): void
    {
        $product = $this->product();
        $session = (string) Str::uuid();
        $address = $this->address('Cebu', 'Cebu City');
        $quote = $this->quote($product->id, $address, $session)->json('data.quotes.0');

        $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/storefront/checkouts', $this->checkoutPayload($product->id, 2, $address, $session, $quote['quote_id']))
            ->assertUnprocessable()->assertJsonValidationErrors('shipping_quote_id');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_selected_shipping_is_added_to_the_authoritative_paymongo_total(): void
    {
        $product = $this->product();
        $session = (string) Str::uuid();
        $address = $this->address('Cebu', 'Cebu City');
        $quote = collect($this->quote($product->id, $address, $session)->json('data.quotes'))->firstWhere('provider', 'maxim');

        $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/storefront/checkouts', $this->checkoutPayload($product->id, 1, $address, $session, $quote['quote_id']))->assertCreated();
        $expected = 75000 + (int) round($quote['fee'] * 100);
        $response->assertJsonPath('data.subtotal_centavos', 75000)->assertJsonPath('data.shipping_centavos', (int) round($quote['fee'] * 100))->assertJsonPath('data.total_centavos', $expected);
        $this->assertDatabaseHas('orders', ['delivery_provider' => 'maxim', 'shipping_quote_id' => $quote['quote_id']]);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.paymongo.com/v1/payment_intents' && $request['data']['attributes']['amount'] === $expected);
    }

    private function quote(int $productId, array $address, ?string $session = null)
    {
        return $this->postJson('/api/v1/storefront/shipping/quotes', ['session_id' => $session ?? (string) Str::uuid(), 'address' => $address, 'items' => [['variant_id' => $productId, 'quantity' => 1]]]);
    }

    private function checkoutPayload(int $productId, int $quantity, array $address, string $session, string $quoteId): array
    {
        return ['customer' => ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '09171234567'], 'delivery_method' => 'shipping', 'shipping_address' => implode(', ', array_values($address)), 'delivery_address' => $address, 'shipping_session_id' => $session, 'shipping_quote_id' => $quoteId, 'items' => [['variant_id' => $productId, 'quantity' => $quantity]]];
    }

    private function address(string $province, string $city): array
    {
        $cebu = $province === 'Cebu';
        $cityCode = $city === 'Mandaue' ? '0731300000' : ($cebu ? '0730600000' : '1380600000');

        return ['country' => 'Philippines', 'region' => $cebu ? 'Region VII (Central Visayas)' : 'National Capital Region (NCR)', 'region_code' => $cebu ? '0700000000' : '1300000000', 'province' => $province, 'city' => $city, 'city_code' => $cityCode, 'barangay' => 'Central', 'barangay_code' => substr($cityCode, 0, 6).'0001', 'postal_code' => '6000', 'street_address' => '123 Test Street'];
    }

    private function product()
    {
        $manager = User::factory()->create(['role' => 'manager']);

        return CreateProductAction::execute(['product_name' => 'ALAS Tee Black S', 'storefront_name' => 'ALAS Tee', 'storefront_slug' => 'alas-tee', 'sku' => 'ALAS-TEE-BLK-S', 'category' => 'T-Shirts', 'color' => 'Black', 'size' => 'S', 'selling_price' => 750, 'cost_price' => 300, 'initial_stock' => 10], $manager);
    }
}
