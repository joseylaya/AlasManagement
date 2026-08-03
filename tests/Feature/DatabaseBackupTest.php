<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_download_a_database_backup(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('maintenance.database-backup'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_from_database_backup(): void
    {
        $this->get(route('maintenance.database-backup'))
            ->assertRedirect(route('login'));
    }
}
