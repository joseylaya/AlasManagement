<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_notification_is_one_row_visible_to_every_target_role_user(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);

        NotificationService::sendBroadcast(
            ['owner', 'manager'],
            'test:review:1',
            'incentive.activity_submitted',
            'Review needed',
            'An activity needs review.',
            '/promotion-activities?activity=1',
        );
        NotificationService::sendBroadcast(
            ['owner', 'manager'],
            'test:review:1',
            'incentive.activity_submitted',
            'Review needed',
            'An activity needs review.',
            '/promotion-activities?activity=1',
        );

        $notification = Notification::sole();

        $this->assertNull($notification->user_id);
        $this->assertSame(['owner', 'manager'], $notification->target_roles);
        $this->assertCount(1, Notification::visibleTo($owner)->get());
        $this->assertCount(1, Notification::visibleTo($manager)->get());
        $this->assertCount(0, Notification::visibleTo($staff)->get());

        $notification->markReadBy($owner);
        $this->assertDatabaseHas('notification_reads', ['notification_id' => $notification->id, 'user_id' => $owner->id]);
        $this->assertDatabaseMissing('notification_reads', ['notification_id' => $notification->id, 'user_id' => $manager->id]);
    }
}
