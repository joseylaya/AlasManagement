<?php

namespace Tests\Feature;

use App\Livewire\Notifications\Manage;
use App\Models\Notification;
use App\Models\RecurringAnnouncement;
use App\Models\User;
use App\Services\RecurringAnnouncementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecurringAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_due_earning_reminder_is_sent_once_per_manila_day(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $staff = User::factory()->create(['role' => 'staff']);
        Carbon::setTestNow(Carbon::parse('2026-08-20 20:01:00', 'Asia/Manila'));

        $campaign = RecurringAnnouncement::create([
            'key' => RecurringAnnouncementService::SOCIAL_SHARING_EARNING_KEY,
            'created_by' => $owner->id,
            'target_role' => 'all',
            'title' => 'Earn by sharing',
            'message' => 'Share approved Facebook or Instagram posts.',
            'send_time' => '20:00:00',
            'timezone' => 'Asia/Manila',
            'is_active' => true,
        ]);

        $this->assertSame(1, RecurringAnnouncementService::dispatchDue());
        $this->assertSame(0, RecurringAnnouncementService::dispatchDue());
        $this->assertDatabaseHas('notifications', [
            'event_key' => 'social-sharing-earning:2026-08-20',
            'type' => 'announcement.earning_opportunity',
        ]);
        $this->assertCount(1, Notification::visibleTo($staff)->get());
        $this->assertSame('2026-08-20', $campaign->fresh()->last_sent_on->toDateString());
    }

    public function test_owner_and_manager_can_edit_the_earning_reminder_but_staff_cannot_open_announcements(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('announcements.index'))->assertForbidden();

        Livewire::actingAs($manager)
            ->test(Manage::class)
            ->set('earning_title', 'Share and earn tonight')
            ->set('earning_message', 'Share the approved Facebook and Instagram posts to earn.')
            ->set('earning_target_role', 'all')
            ->set('earning_send_time', '20:00')
            ->set('earning_is_active', true)
            ->call('saveEarningReminder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('recurring_announcements', [
            'key' => RecurringAnnouncementService::SOCIAL_SHARING_EARNING_KEY,
            'updated_by' => $manager->id,
            'send_time' => '20:00:00',
        ]);
    }
}
