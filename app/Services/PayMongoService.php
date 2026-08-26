<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PayMongoService
{
    public function createCheckoutSession(Order $order): array
    {
        $response = $this->client('test')->post($this->url('/checkout_sessions'), [
            'data' => ['attributes' => [
                'billing' => ['name' => $order->customer_name, 'email' => $order->customer_email, 'phone' => $order->customer_phone],
                'cancel_url' => config('services.paymongo.cancel_url'),
                'description' => "ALAS sandbox order {$order->order_number}",
                'line_items' => $order->items->map(fn ($item) => [
                    'amount' => (int) round(((float) $item->unit_price) * 100), 'currency' => 'PHP',
                    'description' => $item->sku, 'name' => $item->product_name, 'quantity' => (int) $item->quantity,
                ])->values()->all(),
                'payment_method_types' => ['card', 'gcash', 'paymaya'],
                'reference_number' => $order->order_number, 'send_email_receipt' => false,
                'show_description' => true, 'show_line_items' => true,
                'success_url' => config('services.paymongo.success_url').'?order='.$order->public_token,
            ]],
        ])->throw()->json('data');

        $id = $response['id'] ?? null;
        $url = $response['attributes']['checkout_url'] ?? null;
        if (! $id || ! $url) throw new RuntimeException('PayMongo did not return a checkout session URL.');

        $order->update([
            'paymongo_checkout_session_id' => $id, 'paymongo_checkout_url' => $url,
            'paymongo_payment_intent_id' => $response['attributes']['payment_intent']['id'] ?? null,
        ]);

        return ['id' => $id, 'checkout_url' => $url];
    }

    public function assertLiveQrphCapability(): array
    {
        if (config('services.paymongo.mode') !== 'live') {
            throw new RuntimeException('Live QR Ph checkout is disabled by server configuration.');
        }

        $methods = Cache::remember('paymongo:live:payment-method-capabilities', now()->addMinutes(10), function () {
            $data = $this->client('live')->get($this->url('/merchants/capabilities/payment_methods'))->throw()->json();
            $candidates = data_get($data, 'data.attributes.payment_methods')
                ?? data_get($data, 'data.attributes.capabilities')
                ?? data_get($data, 'data')
                ?? (array_is_list($data) ? $data : null)
                ?? [];

            return collect($candidates)->map(fn ($value) => is_array($value) ? ($value['type'] ?? $value['name'] ?? null) : $value)
                ->filter()->map(fn ($value) => strtolower((string) $value))->values()->all();
        });

        if (! in_array('qrph', $methods, true)) {
            throw new RuntimeException('The PayMongo live account does not currently expose the qrph capability.');
        }

        return $methods;
    }

    public function createQrPayment(Order $order, bool $regenerate = false): Order
    {
        $mode = $order->commerce_mode === 'live' ? 'live' : 'test';
        if ($mode === 'live') $this->assertLiveQrphCapability();

        $order->refresh()->loadMissing('items');
        if ($order->payment_status === 'paid') return $order;
        if (! $regenerate && $order->paymongo_qr_image_url && $order->paymongo_qr_expires_at?->isFuture()) return $order;

        try {
            $intentId = $order->paymongo_payment_intent_id;
            if (! $intentId) {
                $intent = $this->client($mode, "paymongo-pi:{$order->id}")
                    ->post($this->url('/payment_intents'), ['data' => ['attributes' => [
                        'amount' => $this->expectedAmount($order), 'currency' => 'PHP',
                        'payment_method_allowed' => ['qrph'],
                        'description' => "ALAS order {$order->order_number}",
                        'metadata' => ['order_number' => $order->order_number, 'order_token' => $order->public_token],
                    ]]])->throw()->json('data');
                $intentId = $intent['id'] ?? null;
                if (! $intentId) throw new RuntimeException('PayMongo did not return a Payment Intent ID.');
                $order->update(['paymongo_payment_intent_id' => $intentId]);
            }

            $attempt = ((int) $order->paymongo_payment_attempt) + 1;
            $method = $this->client($mode, "paymongo-pm:{$order->id}:{$attempt}")
                ->post($this->url('/payment_methods'), ['data' => ['attributes' => [
                    'type' => 'qrph',
                    'billing' => ['name' => $order->customer_name, 'email' => $order->customer_email, 'phone' => $order->customer_phone],
                ]]])->throw()->json('data');
            $methodId = $method['id'] ?? null;
            if (! $methodId) throw new RuntimeException('PayMongo did not return a QR Ph Payment Method ID.');

            $attached = $this->client($mode, "paymongo-attach:{$order->id}:{$attempt}")
                ->post($this->url("/payment_intents/{$intentId}/attach"), ['data' => ['attributes' => [
                    'payment_method' => $methodId,
                    'return_url' => config('services.paymongo.success_url').'?order='.$order->public_token,
                ]]])->throw()->json('data');
            $attributes = $attached['attributes'] ?? [];
            $imageUrl = data_get($attributes, 'next_action.code.image_url');
            if (! is_string($imageUrl) || $imageUrl === '') throw new RuntimeException('PayMongo did not return a Dynamic QR Ph image.');
            $expiresAt = data_get($attributes, 'next_action.code.expires_at');

            $order->update([
                'payment_method' => 'qrph', 'payment_status' => 'pending',
                'paymongo_payment_method_id' => $methodId, 'paymongo_qr_image_url' => $imageUrl,
                'paymongo_qr_expires_at' => is_numeric($expiresAt) ? now()->setTimestamp((int) $expiresAt) : now()->addMinutes(30),
                'paymongo_payment_attempt' => $attempt, 'payment_error_code' => null, 'server_updated_at' => now(),
            ]);

            ActivityLogService::log('QR Ph Payment Created', "Created QR Ph payment for {$order->order_number}.", $order, [
                'payment_intent_id' => $intentId, 'commerce_mode' => $order->commerce_mode, 'attempt' => $attempt,
            ]);

            return $order->fresh('items');
        } catch (Throwable $error) {
            report($error);
            throw new RuntimeException('Unable to create QR Ph payment. Please try again.');
        }
    }

    public function refreshPaymentStatus(Order $order): Order
    {
        if ($order->payment_status === 'paid') return $order;
        if (! $order->paymongo_payment_intent_id && $order->paymongo_checkout_session_id) return $this->refreshCheckoutSession($order);
        if (! $order->paymongo_payment_intent_id) return $order;

        $mode = $order->commerce_mode === 'live' ? 'live' : 'test';
        $intent = $this->client($mode)->get($this->url('/payment_intents/'.$order->paymongo_payment_intent_id))->throw()->json('data');
        $attributes = $intent['attributes'] ?? [];
        if (($attributes['status'] ?? null) === 'succeeded') {
            return $this->finalizePaid($order, $attributes, $intent['id'] ?? null);
        }
        if (in_array($attributes['status'] ?? null, ['failed', 'cancelled'], true)) {
            $order->update(['payment_status' => 'failed', 'payment_error_code' => 'provider_failed', 'server_updated_at' => now()]);
        }

        return $order->fresh('items');
    }

    public function finalizePaid(Order $order, array $attributes, ?string $providerPaymentId = null): Order
    {
        return DB::transaction(function () use ($order, $attributes, $providerPaymentId) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->payment_status === 'paid') return $locked->fresh('items');
            $amount = (int) ($attributes['amount'] ?? $attributes['amount_received'] ?? -1);
            $currency = strtoupper((string) ($attributes['currency'] ?? ''));
            if ($amount !== $this->expectedAmount($locked) || $currency !== 'PHP' || $locked->order_status === 'cancelled') {
                throw new RuntimeException('PayMongo payment did not match the local order.');
            }
            $payments = $attributes['payments'] ?? [];
            $paidPayment = collect($payments)->first(fn ($payment) => data_get($payment, 'attributes.status') === 'paid');
            $paidAt = data_get($paidPayment, 'attributes.paid_at') ?? $attributes['paid_at'] ?? null;
            $locked->update([
                'payment_status' => 'paid', 'paymongo_payment_id' => $paidPayment['id'] ?? $providerPaymentId ?? $locked->paymongo_payment_id,
                'paymongo_qr_image_url' => null, 'payment_error_code' => null,
                'paid_at' => is_numeric($paidAt) ? now()->setTimestamp((int) $paidAt) : now(), 'server_updated_at' => now(),
            ]);
            ActivityLogService::log('Storefront Payment Confirmed', "PayMongo confirmed payment for {$locked->order_number}.", $locked, [
                'commerce_mode' => $locked->commerce_mode, 'payment_intent_id' => $locked->paymongo_payment_intent_id,
            ]);

            return $locked->fresh('items');
        }, 3);
    }

    private function refreshCheckoutSession(Order $order): Order
    {
        $data = $this->client('test')->get($this->url('/checkout_sessions/'.$order->paymongo_checkout_session_id))->throw()->json('data');
        $intent = $data['attributes']['payment_intent']['attributes'] ?? [];
        $payments = $intent['payments'] ?? $data['attributes']['payments'] ?? [];
        if (($intent['status'] ?? null) === 'succeeded' || collect($payments)->contains(fn ($payment) => data_get($payment, 'attributes.status') === 'paid')) {
            $intent['amount'] ??= $this->expectedAmount($order);
            $intent['currency'] ??= 'PHP';
            $intent['payments'] = $payments;
            return $this->finalizePaid($order, $intent, data_get($payments, '0.id'));
        }

        return $order->fresh('items');
    }

    private function client(string $mode, ?string $idempotencyKey = null): PendingRequest
    {
        $secret = $this->secretFor($mode);
        $request = Http::withBasicAuth($secret, '')->acceptJson()->timeout(15);
        return $idempotencyKey ? $request->withHeader('Idempotency-Key', substr($idempotencyKey, 0, 255)) : $request;
    }

    private function secretFor(string $mode): string
    {
        $specific = (string) config("services.paymongo.{$mode}_secret_key");
        $generic = (string) config('services.paymongo.secret_key');
        $secret = $specific !== '' ? $specific : $generic;
        $prefix = $mode === 'live' ? 'sk_live_' : 'sk_test_';
        if (! str_starts_with($secret, $prefix)) throw new RuntimeException("A PayMongo {$mode} secret key is required.");
        return $secret;
    }

    private function expectedAmount(Order $order): int
    {
        return (int) round(((float) $order->total_amount) * 100);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.paymongo.api_url'), '/').$path;
    }
}
