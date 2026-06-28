<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class ParentChildrenApiTest extends TestCase
{
    public function test_parent_children_returns_linked_students(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $auth['school']->id,
        ]);
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $parent->students()->attach($student->id, [
            'school_id' => $auth['school']->id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/parents/{$parent->id}/children");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id);
    }
}
