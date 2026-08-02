<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Index;
use App\Models\CompensationRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffIncentiveVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_incentives_do_not_count_as_staff_earnings(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        CompensationRecord::create([
            'record_number' => 'CMP-000001',
            'user_id' => $staff->id,
            'type' => 'activity_incentive',
            'amount' => 125,
            'status' => 'pending_approval',
        ]);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertSee('₱0.00')
            ->assertDontSee('₱125.00');
    }

    public function test_finance_approved_incentives_count_as_staff_earnings(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        CompensationRecord::create([
            'record_number' => 'CMP-000002',
            'user_id' => $staff->id,
            'type' => 'activity_incentive',
            'amount' => 125,
            'status' => 'payable',
        ]);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertSee('₱125.00');
    }
}
