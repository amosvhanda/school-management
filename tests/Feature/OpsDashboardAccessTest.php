<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OpsDashboardAccessTest extends TestCase
{
    public function test_super_admin_can_view_horizon_and_pulse_gates(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('viewHorizon'));
        $this->assertTrue(Gate::forUser($user)->allows('viewPulse'));
    }

    public function test_school_admin_cannot_view_horizon_or_pulse_gates(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'school_id' => $school->id,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($user)->allows('viewPulse'));
    }
}
