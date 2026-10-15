<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Student;
use Tests\TestCase;

class GuardianApiTest extends TestCase
{
    public function test_get_guardians_list(): void
    {
        $auth = $this->createAuthenticatedUser();
        $guardian = Guardian::factory()->create(['school_id' => $auth['school']->id]);
        $otherGuardian = Guardian::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/guardians');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $guardian->id)
            ->assertJsonPath('data.0.school_id', $auth['school']->id)
            ->assertJsonPath('meta.current_page', 1);

        $this->assertNotContains($otherGuardian->id, array_column($response->json('data'), 'id'));
    }

    public function test_create_guardian_and_link_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/guardians', [
            'first_name' => 'Mary',
            'last_name' => 'Moyo',
            'phone' => '+263771234567',
            'email' => 'mary.moyo@example.com',
            'relationship' => 'parent',
            'student_id' => $student->id,
        ]);

        $create->assertCreated()
            ->assertJsonPath('first_name', 'Mary')
            ->assertJsonPath('school_id', $auth['school']->id);

        $guardianId = $create->json('id');

        $this->assertDatabaseHas('guardians', [
            'id' => $guardianId,
            'first_name' => 'Mary',
            'last_name' => 'Moyo',
            'email' => 'mary.moyo@example.com',
            'school_id' => $auth['school']->id,
        ]);
        $this->assertDatabaseHas('guardian_student', [
            'guardian_id' => $guardianId,
            'student_id' => $student->id,
            'relationship' => 'parent',
            'is_primary' => 1,
        ]);

        $students = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/guardians/{$guardianId}/students");

        $students->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $student->id);
    }

    public function test_link_guardian_to_student(): void
    {
        $auth = $this->createAuthenticatedUser();
        $guardian = Guardian::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson("/api/v1/guardians/{$guardian->id}/link-student", [
            'student_id' => $student->id,
            'relationship' => 'guardian',
            'is_primary' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('id', $guardian->id);

        $this->assertDatabaseHas('guardian_student', [
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'relationship' => 'guardian',
            'is_primary' => 1,
        ]);

        $forStudent = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/guardians");

        $forStudent->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $guardian->id);
    }
}
