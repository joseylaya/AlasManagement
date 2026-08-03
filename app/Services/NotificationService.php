<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\CompensationRecord;
use App\Models\Announcement;
use App\Models\Product;
use App\Models\PromotionActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public static function send(
        User|int $user,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $announcementId = null,
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = Notification::create([
            'user_id' => $userId,
            'announcement_id' => $announcementId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);

        PushNotificationService::send($notification);

        return $notification;
    }

    /** Create one shared notification, visible to all active users in the target roles. */
    public static function sendBroadcast(
        array $roles,
        string $eventKey,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $announcementId = null,
    ): Notification {
        $notification = Notification::firstOrCreate(
            ['event_key' => $eventKey],
            [
                'user_id' => null,
                'announcement_id' => $announcementId,
                'target_roles' => array_values(array_unique($roles)),
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => false,
            ],
        );

        if ($notification->wasRecentlyCreated) {
            PushNotificationService::sendToRoles($notification, $roles);
        }

        return $notification;
    }

    public static function notifyLowStock(Product $product, int $currentStock, int $minThreshold): void
    {
        static::sendBroadcast(
            ['owner', 'manager'],
            "inventory-low-stock:{$product->id}:{$currentStock}",
            'inventory.low_stock',
            'Low Stock Alert',
            "Product {$product->product_name} ({$product->sku}) is low on stock! Current stock: {$currentStock} (Minimum: {$minThreshold}).",
            '/inventory',
        );
    }

    public static function notifyPromotionActivitySubmitted(PromotionActivity $activity): void
    {
        static::sendBroadcast(
            ['owner', 'manager'],
            "incentive-activity-submitted:{$activity->id}",
            'incentive.activity_submitted',
            'Incentive activity needs review',
            "{$activity->user->name} submitted {$activity->activity_type} for {$activity->activity_date->format('M j, Y')}.",
            route('promotion-activities.index', ['activity' => $activity->id]),
        );
    }

    public static function notifyPromotionActivityApproved(PromotionActivity $activity): void
    {
        static::send(
            $activity->user,
            'incentive.activity_verified',
            'Your incentive activity was verified',
            "Your {$activity->activity_type} was verified for ₱".number_format((float) $activity->approved_amount, 2).'. Final finance approval is still pending.',
            route('promotion-activities.index')
        );
    }

    public static function notifyPromotionActivityRejected(PromotionActivity $activity): void
    {
        static::send(
            $activity->user,
            'incentive.activity_declined',
            'Your incentive activity was declined',
            "Your {$activity->activity_type} was not approved.".($activity->review_notes ? " Note: {$activity->review_notes}" : ''),
            route('promotion-activities.index')
        );
    }

    public static function notifyCompensationAwaitingApproval(CompensationRecord $record): void
    {
        static::sendBroadcast(
            ['owner'],
            "incentive-finance-approval:{$record->id}",
            'incentive.finance_approval_required',
            'Incentive needs finance approval',
            "{$record->user->name}'s {$record->record_number} for ₱".number_format((float) $record->amount, 2).' is ready for approval.',
            route('finance.index', ['compensation_status' => 'pending_approval', 'compensation' => $record->id]).'#compensation-approvals',
        );
    }

    public static function notifyCompensationApproved(CompensationRecord $record): void
    {
        static::send(
            $record->user,
            'incentive.finance_approved',
            'Your incentive was approved',
            "Your {$record->record_number} for ₱".number_format((float) $record->amount, 2).' was approved and is ready for payment.',
            route('finance.index')
        );
    }

    /** Deliver one announcement exactly once, even if the scheduler overlaps. */
    public static function publishAnnouncement(Announcement|int $announcement): bool
    {
        return DB::transaction(function () use ($announcement): bool {
            $item = Announcement::lockForUpdate()->findOrFail($announcement instanceof Announcement ? $announcement->id : $announcement);

            if ($item->sent_at !== null || $item->status === 'sent') {
                return false;
            }

            $recipientCount = User::where('status', 'active')
                ->when($item->target_role !== 'all', fn ($query) => $query->where('role', $item->target_role))
                ->count();

            static::sendBroadcast([$item->target_role], "announcement:{$item->id}", 'announcement.general', $item->title, $item->message, route('dashboard'), $item->id);

            $item->update([
                'status' => 'sent',
                'sent_at' => now(),
                'recipient_count' => $recipientCount,
            ]);

            return true;
        });
    }

    /** @return int number of scheduled announcements delivered */
    public static function dispatchScheduledAnnouncements(): int
    {
        $count = 0;

        Announcement::where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('id')
            ->each(function (Announcement $announcement) use (&$count): void {
                if (static::publishAnnouncement($announcement)) {
                    $count++;
                }
            });

        return $count;
    }
}
