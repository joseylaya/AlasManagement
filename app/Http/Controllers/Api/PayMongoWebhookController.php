<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $payMongo): JsonResponse
    {
        $payload = $request->getContent();
        $signatureMode = $this->signatureMode($payload, (string) $request->header('Paymongo-Signature'));
        abort_unless($signatureMode, 401);

        $decoded = json_decode($payload, true);
        $event = $decoded['data'] ?? null;
        abort_unless(is_array($event) && isset($event['id'], $event['attributes']['type']), 400);
        $livemode = (bool) ($event['attributes']['livemode'] ?? false);
        abort_unless(($livemode ? 'live' : 'test') === $signatureMode, 401);

        $failed = false;
        DB::transaction(function () use ($event, $livemode, $payMongo, &$failed) {
            $inserted = DB::table('paymongo_webhook_events')->insertOrIgnore([
                'event_id' => $event['id'], 'event_type' => $event['attributes']['type'], 'livemode' => $livemode,
                'status' => 'received', 'created_at' => now(), 'updated_at' => now(),
            ]);
            if (! $inserted) return;

            $type = $event['attributes']['type'];
            if (! in_array($type, ['payment.paid', 'payment.failed', 'qrph.expired'], true)) {
                $this->markEvent($event['id'], 'ignored');
                return;
            }

            $resource = $event['attributes']['data'] ?? [];
            $attributes = $resource['attributes'] ?? [];
            $intentId = $attributes['payment_intent_id'] ?? data_get($attributes, 'payment_intent.id');
            $reference = $attributes['external_reference_number'] ?? data_get($attributes, 'metadata.order_number');
            $resourceId = $resource['id'] ?? null;
            $order = Order::query()
                ->where(function ($query) use ($intentId, $reference, $resourceId) {
                    if ($intentId) $query->orWhere('paymongo_payment_intent_id', $intentId);
                    if ($reference) $query->orWhere('order_number', $reference);
                    if ($resourceId) $query->orWhere('paymongo_payment_method_id', $resourceId);
                })->lockForUpdate()->first();
            if (! $order) {
                $this->markEvent($event['id'], 'unmatched');
                return;
            }
            DB::table('paymongo_webhook_events')->where('event_id', $event['id'])->update(['order_id' => $order->id, 'updated_at' => now()]);
            if (($order->commerce_mode === 'live') !== $livemode) {
                $this->markEvent($event['id'], 'failed', 'Payment environment did not match the order.');
                $failed = true;
                return;
            }

            try {
                if ($type === 'payment.paid') {
                    $payMongo->finalizePaid($order, $attributes, $resourceId);
                } elseif ($type === 'payment.failed' && $order->payment_status !== 'paid') {
                    $order->update(['payment_status' => 'failed', 'payment_error_code' => 'provider_failed', 'server_updated_at' => now()]);
                } elseif ($type === 'qrph.expired' && $order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'pending', 'paymongo_qr_image_url' => null,
                        'paymongo_qr_expires_at' => now(), 'payment_error_code' => 'qr_expired', 'server_updated_at' => now(),
                    ]);
                }
                $this->markEvent($event['id'], 'processed');
            } catch (RuntimeException $error) {
                $this->markEvent($event['id'], 'failed', $error->getMessage());
                $failed = true;
            }
        }, 3);

        return response()->json(['received' => ! $failed], $failed ? 422 : 200);
    }

    private function signatureMode(string $payload, string $header): ?string
    {
        if ($header === '') return null;
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key && $value) $parts[$key] = $value;
        }
        $timestamp = $parts['t'] ?? null;
        if (! ctype_digit((string) $timestamp)) return null;
        $tolerance = (int) config('services.paymongo.webhook_tolerance', 300);
        if ($tolerance > 0 && abs(now()->timestamp - (int) $timestamp) > $tolerance) return null;

        foreach (['live' => 'li', 'test' => 'te'] as $mode => $signatureKey) {
            $secret = (string) config("services.paymongo.{$mode}_webhook_secret");
            if ($secret === '') {
                $genericKey = (string) config('services.paymongo.secret_key');
                $expectedPrefix = $mode === 'live' ? 'sk_live_' : 'sk_test_';
                if (str_starts_with($genericKey, $expectedPrefix)) $secret = (string) config('services.paymongo.webhook_secret');
            }
            $provided = $parts[$signatureKey] ?? null;
            if ($secret !== '' && $provided && hash_equals(hash_hmac('sha256', $timestamp.'.'.$payload, $secret), $provided)) return $mode;
        }

        return null;
    }

    private function markEvent(string $eventId, string $status, ?string $error = null): void
    {
        DB::table('paymongo_webhook_events')->where('event_id', $eventId)->update([
            'status' => $status, 'error' => $error, 'updated_at' => now(),
        ]);
    }
}
