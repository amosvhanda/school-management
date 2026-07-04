<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Teacher;

class TeacherApiTest extends TestCase
{
    public function test_get_teachers_list(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/v1/teachers');

        $response->assertStatus(200);
    }

    public function test_create_teacher(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/v1/teachers', [
            'name' => 'Jane Smith',
            'email' => 'jane.smith' . fake()->unique()->numberBetween(1000, 9999) . '@example.com',
            'phone' => '+263771234567',
            'subject' => 'Mathematics',
            'department' => 'Science',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
                'message',
            ]);
    }

    public function test_get_teacher_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/teachers/{$teacher->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }

    public function test_cannot_access_teacher_from_different_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = \App\Models\School::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $otherSchool->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/v1/teachers/{$teacher->id}");

        $response->assertStatus(404);
    }

    public function test_update_teacher(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/v1/teachers/{$teacher->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertStatus(200);
    }

    public function test_update_teacher_status(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->patchJson("/api/v1/teachers/{$teacher->id}/status", [
            'status' => 'active',
        ]);

        $response->assertStatus(200);
    }

    public function test_delete_teacher(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/v1/teachers/{$teacher->id}");

        $response->assertStatus(200);
    }
}
