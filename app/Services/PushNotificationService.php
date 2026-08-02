<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public static function send(Notification $notification): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $config = config('services.web_push');

        if (empty($config['public_key']) || empty($config['private_key'])) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $notification->user_id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject' => $config['subject'],
                'publicKey' => $config['public_key'],
                'privateKey' => $config['private_key'],
            ]]);

            $payload = json_encode([
                'title' => $notification->title,
                'body' => $notification->message,
                'url' => $notification->link ?: route('dashboard'),
                'icon' => url('/images/alas-logo-192.png'),
                'tag' => 'alas-notification-'.$notification->id,
            ], JSON_THROW_ON_ERROR);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]), $payload, ['TTL' => 86400]);
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to deliver browser push notification.', ['notification_id' => $notification->id, 'error' => $exception->getMessage()]);
        }
    }
}
