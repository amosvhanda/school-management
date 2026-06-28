<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable API rate limiting for tests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    /**
     * Create an authenticated user for testing
     */
    protected function createAuthenticatedUser($role = 'admin', $schoolId = null)
    {
        $school = $schoolId 
            ? \App\Models\School::find($schoolId)
            : \App\Models\School::factory()->create();

        $user = \App\Models\User::factory()->create([
            'role' => $role,
            'school_id' => $school->id,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'school' => $school,
        ];
    }
}
