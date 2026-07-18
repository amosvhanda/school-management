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
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('data.0.relationship', 'parent')
            ->assertJsonPath('data.0.class_id', $student->class_id);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'school_id' => $auth['school']->id,
            'relationship' => 'parent',
            'is_primary' => 1,
        ]);
    }

    public function test_parent_children_excludes_students_from_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $parent = User::factory()->create([
            'role' => UserRole::Parent,
            'school_id' => $auth['school']->id,
        ]);
        $otherStudent = Student::factory()->create();

        $parent->students()->attach($otherStudent->id, [
            'school_id' => $otherStudent->school_id,
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/parents/{$parent->id}/children")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
