<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Teacher;
use Tests\TestCase;

class DepartmentApiTest extends TestCase
{
    public function test_get_departments_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $department = Department::factory()->create(['school_id' => $auth['school']->id]);
        $otherDepartment = Department::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/departments');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $department->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id);

        $this->assertNotContains($otherDepartment->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_department(): void
    {
        $auth = $this->createAuthenticatedUser();
        $head = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/departments', [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'description' => 'Math department',
            'head_teacher_id' => $head->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Mathematics')
            ->assertJsonPath('data.head_teacher_id', $head->id)
            ->assertJsonPath('data.school_id', $auth['school']->id);

        $this->assertDatabaseHas('departments', [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'description' => 'Math department',
            'head_teacher_id' => $head->id,
            'school_id' => $auth['school']->id,
            'is_active' => 1,
        ]);
    }

    public function test_update_department(): void
    {
        $auth = $this->createAuthenticatedUser();
        $department = Department::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/departments/{$department->id}", [
            'description' => 'Updated description',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_delete_department(): void
    {
        $auth = $this->createAuthenticatedUser();
        $department = Department::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/departments/{$department->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Department deleted successfully');

        $this->assertDatabaseMissing('departments', [
            'id' => $department->id,
        ]);
    }
}
