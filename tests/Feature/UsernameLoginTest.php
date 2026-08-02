<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'owner',
            'password' => 'password',
            'status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('username', 'OWNER')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'username' => 'staff',
            'password' => 'password',
            'status' => 'inactive',
        ]);

        Livewire::test(Login::class)
            ->set('username', 'staff')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }
}
