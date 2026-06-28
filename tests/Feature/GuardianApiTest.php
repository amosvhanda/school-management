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
        Guardian::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/guardians');

        $response->assertOk();
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
            ->assertJsonPath('first_name', 'Mary');

        $guardianId = $create->json('id');

        $students = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/guardians/{$guardianId}/students");

        $students->assertOk()
            ->assertJsonCount(1);
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

        $response->assertOk();

        $forStudent = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}/guardians");

        $forStudent->assertOk()
            ->assertJsonCount(1);
    }
}
