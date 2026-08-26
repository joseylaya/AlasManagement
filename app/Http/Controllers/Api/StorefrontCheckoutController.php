<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateStorefrontOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorefrontCheckoutRequest;
use App\Models\Order;
use App\Services\GuestOrderTrackingMailer;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class StorefrontCheckoutController extends Controller
{
    public function store(StorefrontCheckoutRequest $request, PayMongoService $payMongo, GuestOrderTrackingMailer $trackingMailer): JsonResponse
    {
        $key = (string) $request->header('Idempotency-Key');
        if (! Str::isUuid($key)) {
            return response()->json(['message' => 'A UUID Idempotency-Key header is required.', 'code' => 'INVALID_IDEMPOTENCY_KEY'], 422);
        }

        $sandbox = $request->header('X-Commerce-Mode') === 'sandbox';
        $legacySandboxCheckout = $sandbox && $request->header('X-Payment-Flow') === 'checkout_session';
        if ($sandbox && (! config('services.storefront_sandbox.enabled') || ! hash_equals((string) config('services.storefront_sandbox.token'), (string) $request->header('X-Sandbox-Token')))) {
            return response()->json(['message' => 'Sandbox checkout is not authorized.', 'code' => 'SANDBOX_UNAUTHORIZED'], 403);
        }

        if (! $sandbox) {
            try {
                $payMongo->assertLiveQrphCapability();
            } catch (RuntimeException $error) {
                return response()->json(['message' => $error->getMessage(), 'code' => 'QRPH_UNAVAILABLE'], 503);
            }
        }

        $order = CreateStorefrontOrderAction::execute($request->validated(), $key, $sandbox ? 'sandbox' : 'live');
        $trackingMailer->send($order);
        if ($legacySandboxCheckout && ! $order->paymongo_checkout_url) {
            $payMongo->createCheckoutSession($order);
            $order->refresh()->load('items');
        } elseif (! $order->paymongo_qr_image_url && $order->payment_status !== 'paid') {
            try {
                $order = $payMongo->createQrPayment($order);
            } catch (RuntimeException $error) {
                return response()->json([
                    'message' => $error->getMessage(), 'code' => 'QRPH_CREATION_FAILED',
                    'data' => $this->serialize($order->fresh('items')),
                ], 502);
            }
        }

        return response()->json(['data' => $this->serialize($order)], 201);
    }

    public function show(Request $request, string $publicToken): JsonResponse
    {
        abort_unless(Str::isUuid($publicToken), 404);
        $order = Order::with('items')->where('public_token', $publicToken)->firstOrFail();

        return response()->json(['data' => $this->serialize($order)]);
    }

    public function refreshPayment(Request $request, string $publicToken, PayMongoService $payMongo): JsonResponse
    {
        abort_unless(Str::isUuid($publicToken), 404);
        $order = Order::with('items')->where('public_token', $publicToken)->firstOrFail();
        $order = $payMongo->refreshPaymentStatus($order);

        return response()->json(['data' => $this->serialize($order)]);
    }

    public function regenerateQr(Request $request, string $publicToken, PayMongoService $payMongo): JsonResponse
    {
        abort_unless(Str::isUuid($publicToken), 404);
        $order = Order::with('items')->where('public_token', $publicToken)->firstOrFail();
        if ($order->payment_status === 'paid' || $order->order_status === 'cancelled') {
            return response()->json(['message' => 'This order cannot create another payment.', 'code' => 'ORDER_NOT_PAYABLE'], 409);
        }
        if ($order->paymongo_qr_expires_at?->isFuture() && $order->paymongo_qr_image_url) {
            return response()->json(['data' => $this->serialize($order)]);
        }

        try {
            $order = $payMongo->createQrPayment($order, regenerate: true);
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage(), 'code' => 'QRPH_CREATION_FAILED'], 502);
        }

        return response()->json(['data' => $this->serialize($order)]);
    }

    private function serialize(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'tracking_token' => $order->public_token,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'delivery_method' => $order->delivery_method,
            'delivery_provider' => $order->delivery_provider,
            'delivery_service' => $order->delivery_service,
            'shipping_status' => $order->shipping_status,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'tracking_email_sent' => $order->tracking_email_sent_at !== null,
            'commerce_mode' => $order->commerce_mode,
            'checkout_url' => $order->paymongo_checkout_url,
            'payment_method' => $order->payment_method,
            'qr_image_url' => $order->payment_status === 'pending' ? $order->paymongo_qr_image_url : null,
            'qr_expires_at' => $order->paymongo_qr_expires_at?->toIso8601String(),
            'payment_error_code' => $order->payment_error_code,
            'currency' => $order->currency,
            'total_centavos' => (int) round(((float) $order->total_amount) * 100),
            'subtotal_centavos' => (int) round(((float) $order->subtotal_amount) * 100),
            'shipping_centavos' => (int) round(((float) $order->shipping_amount) * 100),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price_centavos' => (int) round(((float) $item->unit_price) * 100),
            ])->values(),
        ];
    }
}
