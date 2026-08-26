<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    /** @param array<int, int> $userIds */
    public function __construct(public int $notificationId, public array $userIds)
    {
    }

    public function handle(PushNotificationService $push): void
    {
        $notification = Notification::find($this->notificationId);

        if ($notification) {
            $push->deliverToUserIds($notification, $this->userIds);
        }
    }
}
