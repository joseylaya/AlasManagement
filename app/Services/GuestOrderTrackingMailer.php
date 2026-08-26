<?php

namespace App\Services;

use App\Mail\GuestOrderTrackingMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GuestOrderTrackingMailer
{
    public function send(Order $order): bool
    {
        if ($order->tracking_email_sent_at || ! $order->customer_email || ! $order->public_token) {
            return false;
        }
        if (config('mail.default') === 'log') {
            Log::warning('Guest tracking email not sent because the log mailer is configured.', ['order_id' => $order->id, 'order_number' => $order->order_number]);

            return false;
        }
        $baseUrl = rtrim((string) config('services.storefront.url'), '/');
        if (! str_starts_with($baseUrl, 'https://') && ! app()->environment('local', 'testing')) {
            Log::warning('Guest tracking email not sent because STOREFRONT_URL is not HTTPS.', ['order_id' => $order->id]);

            return false;
        }
        try {
            Mail::to($order->customer_email)->send(new GuestOrderTrackingMail($order->loadMissing('items'), "{$baseUrl}/orders/{$order->public_token}"));
            $order->forceFill(['tracking_email_sent_at' => now()])->save();

            return true;
        } catch (Throwable $error) {
            Log::error('Guest tracking email failed.', ['order_id' => $order->id, 'order_number' => $order->order_number, 'error' => $error->getMessage()]);

            return false;
        }
    }
}
