<?php

namespace Tests\Feature;

use App\Livewire\Account\Index;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_member_can_change_their_own_password(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'password' => 'old-password']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->set('current_password', 'old-password')
            ->set('password', 'new-secure-password')
            ->set('password_confirmation', 'new-secure-password')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSee('Password updated successfully.');

        $this->assertTrue(Hash::check('new-secure-password', $staff->fresh()->password));
        $this->assertDatabaseHas(ActivityLog::class, ['user_id' => $staff->id, 'action' => 'Password Changed']);
        $this->assertDatabaseHas(Notification::class, ['user_id' => $staff->id, 'type' => 'account.password_changed']);
    }

    public function test_incorrect_current_password_is_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'password' => 'old-password']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->set('current_password', 'not-the-current-password')
            ->set('password', 'new-secure-password')
            ->set('password_confirmation', 'new-secure-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $staff->fresh()->password));
    }
}
