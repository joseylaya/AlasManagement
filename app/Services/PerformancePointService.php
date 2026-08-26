<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PerformancePointEntry;
use App\Models\PromotionActivity;
use App\Models\User;
use Illuminate\Support\Collection;

class PerformancePointService
{
    public const ACTIVITY_SUBMITTED_POINTS = 1;
    public const ORDER_SUBMITTED_POINTS = 3;
    public const ORDER_COMPLETED_POINTS = 5;

    public static function awardActivitySubmitted(PromotionActivity $activity): ?PerformancePointEntry
    {
        return static::award($activity->user, 'promotion_activity', $activity->id, PerformancePointEntry::ACTIVITY_SUBMITTED, static::ACTIVITY_SUBMITTED_POINTS);
    }

    public static function awardOrderSubmitted(Order $order): ?PerformancePointEntry
    {
        return static::award($order->creator ?? $order->user, 'order', $order->id, PerformancePointEntry::ORDER_SUBMITTED, static::ORDER_SUBMITTED_POINTS);
    }

    public static function awardOrderCompleted(Order $order): ?PerformancePointEntry
    {
        return static::award($order->creator ?? $order->user, 'order', $order->id, PerformancePointEntry::ORDER_COMPLETED, static::ORDER_COMPLETED_POINTS);
    }

    /** @return Collection<int, object{user: User, points: int, rank: int}> */
    public static function hallOfFameLeaderboard(): Collection
    {
        $totals = PerformancePointEntry::query()
            ->selectRaw('user_id, SUM(points) as points')
            ->groupBy('user_id');

        return User::query()
            ->where('status', 'active')
            ->whereIn('role', ['staff', 'manager'])
            ->leftJoinSub($totals, 'performance_totals', fn ($join) => $join->on('users.id', '=', 'performance_totals.user_id'))
            ->select('users.*')
            ->selectRaw('COALESCE(performance_totals.points, 0) as performance_points')
            ->orderByDesc('performance_points')
            ->orderBy('users.name')
            ->get()
            ->values()
            ->map(function (User $user, int $index): object {
                return (object) [
                    'user' => $user,
                    'points' => (int) $user->performance_points,
                    'rank' => $index + 1,
                ];
            });
    }

    /** @return array{total: int, activity: int, order_submitted: int, order_completed: int} */
    public static function hallOfFameSummary(User $user): array
    {
        $entries = PerformancePointEntry::query()
            ->where('user_id', $user->id)
            ->selectRaw('event, SUM(points) as points')
            ->groupBy('event')
            ->pluck('points', 'event');

        $activity = (int) ($entries[PerformancePointEntry::ACTIVITY_SUBMITTED] ?? 0);
        $submitted = (int) ($entries[PerformancePointEntry::ORDER_SUBMITTED] ?? 0);
        $completed = (int) ($entries[PerformancePointEntry::ORDER_COMPLETED] ?? 0);

        return ['total' => $activity + $submitted + $completed, 'activity' => $activity, 'order_submitted' => $submitted, 'order_completed' => $completed];
    }

    private static function award(?User $user, string $sourceType, int $sourceId, string $event, int $points): ?PerformancePointEntry
    {
        if (!$user || !in_array($user->role, ['staff', 'manager'], true)) {
            return null;
        }

        return PerformancePointEntry::firstOrCreate(
            ['source_type' => $sourceType, 'source_id' => $sourceId, 'event' => $event],
            ['user_id' => $user->id, 'points' => $points, 'awarded_at' => now()],
        );
    }
}
