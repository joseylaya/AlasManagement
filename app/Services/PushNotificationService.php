<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Jobs\SendWebPushNotification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public static function send(Notification $notification): void
    {
        static::queueForUserIds($notification, [$notification->user_id]);
    }

    public static function sendToRoles(Notification $notification, array $roles): void
    {
        $userIds = User::where('status', 'active')
            ->when(!in_array('all', $roles, true), fn ($query) => $query->whereIn('role', $roles))
            ->pluck('id')
            ->all();

        static::queueForUserIds($notification, $userIds);
    }

    /** @param array<int, int> $userIds */
    private static function queueForUserIds(Notification $notification, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        SendWebPushNotification::dispatch($notification->id, array_values(array_unique($userIds)))->afterCommit();
    }

    /** @param array<int, int> $userIds */
    public function deliverToUserIds(Notification $notification, array $userIds): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $config = config('services.web_push');

        if (empty($config['public_key']) || empty($config['private_key'])) {
            return;
        }

        $subscriptions = PushSubscription::whereIn('user_id', array_filter($userIds))->get();
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
