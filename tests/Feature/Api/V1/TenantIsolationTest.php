<?php

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_student_from_another_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $userA = User::factory()->create([
            'role' => 'admin',
            'school_id' => $schoolA->id,
            'password' => bcrypt('password'),
        ]);

        $studentB = Student::factory()->create(['school_id' => $schoolB->id]);

        $token = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/v1/students/{$studentB->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_view_student_from_own_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson("/api/v1/students/{$student->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $student->id);
    }

    public function test_school_isolation_middleware_blocks_cross_school_id_in_request(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $userA = User::factory()->create([
            'role' => 'admin',
            'school_id' => $schoolA->id,
            'password' => bcrypt('password'),
        ]);

        $token = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/students', [
            'firstName' => 'John',
            'surname' => 'Doe',
            'class' => 'Form 1A',
            'school_id' => $schoolB->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_students_list_only_returns_own_school_data(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        Student::factory()->count(2)->create(['school_id' => $auth['school']->id]);
        Student::factory()->count(3)->create(['school_id' => $otherSchool->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->getJson('/api/v1/students');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
