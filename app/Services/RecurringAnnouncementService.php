<?php

namespace App\Services;

use App\Models\RecurringAnnouncement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringAnnouncementService
{
    public const SOCIAL_SHARING_EARNING_KEY = 'social-sharing-earning';

    public static function dispatchDue(): int
    {
        $sent = 0;

        RecurringAnnouncement::where('is_active', true)->orderBy('id')->each(function (RecurringAnnouncement $campaign) use (&$sent): void {
            if (static::dispatchCampaignIfDue($campaign->id)) {
                $sent++;
            }
        });

        return $sent;
    }

    private static function dispatchCampaignIfDue(int $campaignId): bool
    {
        return DB::transaction(function () use ($campaignId): bool {
            $campaign = RecurringAnnouncement::lockForUpdate()->find($campaignId);

            if (! $campaign || ! $campaign->is_active) {
                return false;
            }

            $now = Carbon::now($campaign->timezone);
            $today = $now->toDateString();
            $sendTime = substr((string) $campaign->send_time, 0, 5);

            if ($now->format('H:i') < $sendTime || $campaign->last_sent_on?->toDateString() === $today) {
                return false;
            }

            NotificationService::sendBroadcast(
                [$campaign->target_role],
                "{$campaign->key}:{$today}",
                'announcement.earning_opportunity',
                $campaign->title,
                $campaign->message,
                route('promotion-activities.index'),
            );

            $campaign->update(['last_sent_on' => $today]);

            return true;
        });
    }
}
