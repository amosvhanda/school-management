<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningPhase1Test extends TestCase
{
    public function test_user_create_without_password_returns_temporary_and_requires_change(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ])->postJson('/api/v1/users', [
            'first_name' => 'Temp',
            'last_name' => 'User',
            'email' => 'temp.user@school.test',
            'role' => 'teacher',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['temporary_password', 'data' => ['id', 'email']]);

        $temporary = $response->json('temporary_password');
        $this->assertNotSame('password123', $temporary);
        $this->assertGreaterThanOrEqual(12, strlen((string) $temporary));

        $user = User::where('email', 'temp.user@school.test')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temporary, $user->password));
    }

    public function test_password_reset_returns_temporary_password_and_blocks_other_routes(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $target = User::factory()->create([
            'school_id' => $auth['school']->id,
            'role' => 'teacher',
            'must_change_password' => false,
        ]);

        $reset = $this->withToken($auth['token'])
            ->postJson("/api/v1/users/{$target->id}/reset-password");

        $reset->assertOk()
            ->assertJsonStructure(['temporary_password', 'data' => ['temporary_password']]);

        $temporary = $reset->json('temporary_password');
        $this->assertNotSame('password123', $temporary);
        $this->assertTrue($target->fresh()->must_change_password);

        $this->flushHeaders();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $target->email,
            'password' => $temporary,
        ]);

        $login->assertOk()
            ->assertJsonPath('data.user.id', $target->id)
            ->assertJsonPath('data.user.must_change_password', true);

        // Authenticate as the reset user to assert middleware enforcement.
        $this->actingAs($target->fresh(), 'sanctum')
            ->getJson('/api/v1/students')
            ->assertForbidden()
            ->assertJsonPath('code', 'must_change_password');

        $this->actingAs($target->fresh(), 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.must_change_password', true);
    }

    public function test_change_password_clears_must_change_flag(): void
    {
        $user = User::factory()->create([
            'password' => 'TempPassw0rd!',
            'must_change_password' => true,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'TempPassw0rd!',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/user/change-password', [
                'old_password' => 'TempPassw0rd!',
                'new_password' => 'NewSecurePass1!',
                'new_password_confirmation' => 'NewSecurePass1!',
            ])
            ->assertOk();

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_teacher_cannot_update_teacher_status(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->patchJson("/api/v1/teachers/{$teacher->id}/status", [
                'status' => 'on_leave',
            ])
            ->assertForbidden();
    }

    public function test_finance_user_cannot_delete_student(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'finance');
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/students/{$student->id}")
            ->assertForbidden();
    }

    public function test_teacher_cannot_read_or_create_student_invoices(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/invoices")
            ->assertForbidden();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/students/{$student->id}/invoices", [
                'amount' => 100,
                'description' => 'Tuition',
                'dueDate' => now()->addDays(14)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_finance_user_can_read_student_invoices(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'finance');
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/invoices")
            ->assertOk();
    }
}
