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

    public static function notifyLowStock(Product $product, int $currentStock, int $minThreshold): void
    {
        $ownersAndManagers = User::whereIn('role', ['owner', 'manager'])->get();

        foreach ($ownersAndManagers as $user) {
            static::send(
                $user,
                'inventory.low_stock',
                'Low Stock Alert',
                "Product {$product->product_name} ({$product->sku}) is low on stock! Current stock: {$currentStock} (Minimum: {$minThreshold}).",
                '/inventory'
            );
        }
    }

    public static function notifyPromotionActivitySubmitted(PromotionActivity $activity): void
    {
        $reviewers = User::whereIn('role', ['owner', 'manager'])
            ->where('status', 'active')
            ->where('id', '!=', $activity->user_id)
            ->get();

        foreach ($reviewers as $reviewer) {
            static::send(
                $reviewer,
                'incentive.activity_submitted',
                'Incentive activity needs review',
                "{$activity->user->name} submitted {$activity->activity_type} for {$activity->activity_date->format('M j, Y')}.",
                route('promotion-activities.index', ['activity' => $activity->id])
            );
        }
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
        foreach (User::where('role', 'owner')->where('status', 'active')->get() as $owner) {
            static::send(
                $owner,
                'incentive.finance_approval_required',
                'Incentive needs finance approval',
                "{$record->user->name}'s {$record->record_number} for ₱".number_format((float) $record->amount, 2).' is ready for approval.',
                route('finance.index', ['compensation_status' => 'pending_approval', 'compensation' => $record->id]).'#compensation-approvals'
            );
        }
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

            $recipients = User::where('status', 'active')
                ->when($item->target_role !== 'all', fn ($query) => $query->where('role', $item->target_role))
                ->get();

            foreach ($recipients as $recipient) {
                static::send($recipient, 'announcement.general', $item->title, $item->message, route('dashboard'), $item->id);
            }

            $item->update([
                'status' => 'sent',
                'sent_at' => now(),
                'recipient_count' => $recipients->count(),
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
