<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\GradeLevel;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    public function test_login_creates_audit_and_login_history(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $auth['user']->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseHas('login_history', [
            'user_id' => $auth['user']->id,
            'event' => 'login',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $auth['user']->id,
            'module' => 'auth',
            'action' => 'login',
        ]);
    }

    public function test_logout_creates_audit_entry(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $auth['user']->id,
            'module' => 'auth',
            'action' => 'logout',
        ]);
    }

    public function test_payment_reverse_is_audited(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $payment = Payment::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
            'status' => 'completed',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/payments/{$payment->id}/reverse", ['reason' => 'Duplicate entry'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'finance',
            'action' => 'payment_reversed',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
        ]);
    }

    public function test_report_export_is_audited(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/reports/export?type=financial&format=json')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'reports',
            'action' => 'export',
        ]);
    }

    public function test_admin_can_list_audit_logs(): void
    {
        $auth = $this->createAuthenticatedUser();
        AuditLog::create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'module' => 'student',
            'action' => 'created',
            'description' => 'Test audit',
            'created_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/audit-logs?module=student')
            ->assertOk();

        $this->assertTrue(
            collect($response->json('data'))->contains(fn ($row) => $row['module'] === 'student'),
            'Expected student module audit log in results'
        );
    }

    public function test_exam_approval_workflow(): void
    {
        $auth = $this->createAuthenticatedUser();
        $term = Term::factory()->create(['school_id' => $auth['school']->id]);
        $gradeLevel = GradeLevel::factory()->create(['school_id' => $auth['school']->id]);
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'grade_level_id' => $gradeLevel->id,
        ]);
        $exam = Exam::factory()->create([
            'school_id' => $auth['school']->id,
            'term_id' => $term->id,
            'grade_level_id' => $gradeLevel->id,
            'subject_id' => $subject->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/exams/{$exam->id}/results", [
                'results' => [[
                    'student_id' => $student->id,
                    'marks_obtained' => 75,
                ]],
            ])
            ->assertOk();

        $result = ExamResult::where('exam_id', $exam->id)->first();
        $this->assertSame('draft', $result->status);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/exams/{$exam->id}/results/approve")
            ->assertOk();

        $this->assertSame('approved', $result->fresh()->status);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/exams/{$exam->id}/publish")
            ->assertOk();

        $this->assertTrue($exam->fresh()->is_published);
    }

    public function test_student_lifecycle_endpoint_returns_360_view(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/students/{$student->id}/lifecycle")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'student',
                    'admission',
                    'academics',
                    'attendance',
                    'finance',
                    'discipline',
                ],
            ]);
    }

    public function test_finance_reconciliation_endpoint(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/finance/reconciliation?period=daily')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['period', 'from', 'to', 'collected', 'by_payment_method'],
            ]);
    }

    public function test_mutating_api_requests_are_logged_in_audit_trail(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/announcements', [
                'title' => 'Audit trail notice',
                'message' => 'Logged activity',
                'type' => 'info',
                'target_audience' => 'all',
                'date' => now()->toDateString(),
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $auth['user']->id,
            'school_id' => $auth['school']->id,
            'module' => 'communication',
            'action' => 'http_post',
        ]);

        $this->assertTrue(
            AuditLog::query()
                ->where('user_id', $auth['user']->id)
                ->where('action', 'created')
                ->where('module', 'communication')
                ->exists(),
            'Expected model-level announcement created audit entry'
        );
    }

    public function test_failed_mutating_requests_are_not_http_audited(): void
    {
        $auth = $this->createAuthenticatedUser();
        $before = AuditLog::query()->where('action', 'http_post')->count();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/announcements', [
                'title' => '',
            ])
            ->assertStatus(422);

        $this->assertSame(
            $before,
            AuditLog::query()->where('action', 'http_post')->count()
        );
    }
}
