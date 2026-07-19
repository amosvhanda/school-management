<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Teacher;
use Tests\TestCase;

class TeacherApiTest extends TestCase
{
    public function test_create_teacher_with_first_name_and_payroll_fields(): void
    {
        $auth = $this->createAuthenticatedUser();
        $email = 'tendai.moyo'.fake()->unique()->numberBetween(1000, 9999).'@example.com';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/teachers', [
            'firstName' => 'Tendai',
            'surname' => 'Moyo',
            'email' => $email,
            'phone' => '+263771234567',
            'subject' => 'Mathematics',
            'department' => 'Science',
            'qualification' => 'BEd',
            'joiningDate' => '2026-01-15',
            'status' => 'active',
            'employment_type' => 'full_time',
            'base_salary' => 850,
            'salary_currency' => 'USD',
            'allowances' => ['housing' => 50],
            'deductions' => ['nssa' => 20],
            'bank_name' => 'CBZ',
            'bank_account_number' => '1234567890',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.first_name', 'Tendai')
            ->assertJsonPath('data.last_name', 'Moyo')
            ->assertJsonPath('data.name', 'Tendai Moyo')
            ->assertJsonPath('data.email', $email)
            ->assertJsonPath('data.base_salary', 850)
            ->assertJsonPath('data.salary_currency', 'USD')
            ->assertJsonPath('data.employment_type', 'full_time')
            ->assertJsonPath('data.bank_name', 'CBZ')
            ->assertJsonPath('data.payment_method', 'bank_transfer');

        $this->assertDatabaseHas('teachers', [
            'id' => $response->json('data.id'),
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'email' => $email,
            'base_salary' => 850,
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_create_teacher_rejects_duplicate_email_in_same_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $email = 'dup.teacher'.fake()->unique()->numberBetween(1000, 9999).'@example.com';
        Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'email' => $email,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/teachers', [
            'firstName' => 'Another',
            'surname' => 'Teacher',
            'email' => $email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_get_teachers_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);
        $otherTeacher = Teacher::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/teachers?all=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $teacher->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id);

        $this->assertNotContains($otherTeacher->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_teacher(): void
    {
        $auth = $this->createAuthenticatedUser();
        $email = 'jane.smith'.fake()->unique()->numberBetween(1000, 9999).'@example.com';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/teachers', [
            'name' => 'Jane Smith',
            'email' => $email,
            'phone' => '+263771234567',
            'subject' => 'Mathematics',
            'department' => 'Science',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'school_id', 'status'],
                'message',
            ])
            ->assertJsonPath('data.name', 'Jane Smith')
            ->assertJsonPath('data.email', $email)
            ->assertJsonPath('data.school_id', $auth['school']->id)
            ->assertJsonPath('data.status', 'active');

        $teacherId = $response->json('data.id');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacherId,
            'name' => 'Jane Smith',
            'email' => $email,
            'subject' => 'Mathematics',
            'department' => 'Science',
            'status' => 'active',
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_get_teacher_by_id(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/teachers/{$teacher->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'school_id']])
            ->assertJsonPath('data.id', $teacher->id)
            ->assertJsonPath('data.school_id', $auth['school']->id);
    }

    public function test_cannot_access_teacher_from_different_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();
        $teacher = Teacher::factory()->create(['school_id' => $otherSchool->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/teachers/{$teacher->id}");

        $response->assertStatus(404);
    }

    public function test_update_teacher(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->putJson("/api/v1/teachers/{$teacher->id}", [
            'firstName' => 'Updated',
            'surname' => 'Name',
            'department' => 'Languages',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.last_name', 'Name')
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.department', 'Languages');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'name' => 'Updated Name',
            'department' => 'Languages',
        ]);
    }

    public function test_update_teacher_status(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->patchJson("/api/v1/teachers/{$teacher->id}/status", [
            'status' => 'on_leave',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'on_leave')
            ->assertJsonPath('message', 'Teacher status updated successfully');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'status' => 'on_leave',
        ]);
    }

    public function test_delete_teacher(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->deleteJson("/api/v1/teachers/{$teacher->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Teacher deleted successfully');

        $this->assertDatabaseMissing('teachers', [
            'id' => $teacher->id,
        ]);
    }
}
