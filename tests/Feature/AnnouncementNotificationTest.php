<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_is_delivered_once_to_the_selected_active_role(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $staff = User::factory()->create(['role' => 'staff']);
        $inactiveStaff = User::factory()->create(['role' => 'staff', 'status' => 'inactive']);
        $manager = User::factory()->create(['role' => 'manager']);

        $announcement = Announcement::create([
            'created_by' => $owner->id,
            'target_role' => 'staff',
            'title' => 'New collection briefing',
            'message' => 'Please review the new clothing designs.',
            'status' => 'draft',
        ]);

        $this->assertTrue(NotificationService::publishAnnouncement($announcement));
        $this->assertFalse(NotificationService::publishAnnouncement($announcement));

        $this->assertDatabaseHas('notifications', [
            'user_id' => null,
            'announcement_id' => $announcement->id,
            'type' => 'announcement.general',
        ]);
        $this->assertSame(1, \App\Models\Notification::where('announcement_id', $announcement->id)->count());
        $this->assertCount(1, \App\Models\Notification::visibleTo($staff)->get());
        $this->assertCount(0, \App\Models\Notification::visibleTo($inactiveStaff)->get());
        $this->assertCount(0, \App\Models\Notification::visibleTo($manager)->get());
        $this->assertSame(1, $announcement->fresh()->recipient_count);
        $this->assertSame('sent', $announcement->fresh()->status);
    }

    public function test_due_scheduled_announcement_is_delivered(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager']);

        $announcement = Announcement::create([
            'created_by' => $owner->id,
            'target_role' => 'manager',
            'title' => 'Store rule update',
            'message' => 'Use the updated opening checklist tomorrow.',
            'status' => 'scheduled',
            'scheduled_for' => now()->subMinute(),
        ]);

        $this->assertSame(1, NotificationService::dispatchScheduledAnnouncements());
        $this->assertDatabaseHas('notifications', ['user_id' => null, 'announcement_id' => $announcement->id]);
        $this->assertSame(0, NotificationService::dispatchScheduledAnnouncements());
    }
}
