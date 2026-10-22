<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\School;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffAttendanceSummaryAndCertificateDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): array
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
        ]);
        Sanctum::actingAs($user);

        return compact('school', 'user');
    }

    public function test_staff_attendance_monthly_summary(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $teacher = Teacher::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        StaffAttendance::create([
            'school_id' => $school->id,
            'date' => now()->startOfMonth()->toDateString(),
            'staff_type' => 'teacher',
            'staff_id' => $teacher->id,
            'status' => 'present',
        ]);
        StaffAttendance::create([
            'school_id' => $school->id,
            'date' => now()->startOfMonth()->addDay()->toDateString(),
            'staff_type' => 'teacher',
            'staff_id' => $teacher->id,
            'status' => 'absent',
        ]);

        $this->getJson('/api/v1/staff-attendance/summary?year='.now()->year.'&month='.now()->month)
            ->assertOk()
            ->assertJsonPath('data.total_marks', 2)
            ->assertJsonPath('data.by_status.present', 1)
            ->assertJsonPath('data.by_status.absent', 1)
            ->assertJsonPath('data.staff.0.staff_id', $teacher->id);
    }

    public function test_certificate_html_download(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();
        $student = Student::factory()->create(['school_id' => $school->id]);

        $certificate = Certificate::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'certificate_type' => 'character',
            'title' => 'Certificate of Character',
            'verification_code' => 'ABC123XYZ',
            'content_html' => '<h1>Hello '.$student->full_name.'</h1>',
            'issued_by' => $user->id,
            'issued_at' => now(),
        ]);

        $response = $this->get('/api/v1/school-certificates/'.$certificate->id.'/download');

        $response->assertOk();
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString($student->full_name, $response->getContent());
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_certificate_verify_and_revoke(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();
        $student = Student::factory()->create(['school_id' => $school->id]);

        $certificate = Certificate::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'certificate_type' => 'character',
            'title' => 'Certificate of Character',
            'verification_code' => 'VERIFY-CODE-1',
            'content_html' => '<p>Valid</p>',
            'issued_by' => $user->id,
            'issued_at' => now(),
        ]);

        $this->getJson('/api/v1/certificates/verify/VERIFY-CODE-1')
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.verification_code', 'VERIFY-CODE-1');

        $revoked = $this->postJson('/api/v1/school-certificates/'.$certificate->id.'/revoke')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($revoked['revoked_at']);

        $this->getJson('/api/v1/certificates/verify/VERIFY-CODE-1')
            ->assertNotFound();

        $this->getJson('/api/v1/school-certificates/'.$certificate->id.'/download')
            ->assertStatus(422);
    }

    public function test_staff_roster_includes_teachers_on_leave(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'status' => 'on_leave',
        ]);

        $roster = $this->getJson('/api/v1/staff-attendance/roster?date='.now()->toDateString())
            ->assertOk()
            ->json('data');

        $match = collect($roster['teachers'])->firstWhere('staff_id', $teacher->id);
        $this->assertNotNull($match);
        $this->assertSame('on_leave', $match['status']);
    }
}
