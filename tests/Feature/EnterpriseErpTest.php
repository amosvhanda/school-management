<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\ChartOfAccount;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Enterprise\FinanceEnterpriseService;
use Tests\TestCase;

class EnterpriseErpTest extends TestCase
{
    public function test_curriculum_versioning_and_weighted_grade(): void
    {
        $auth = $this->createAuthenticatedUser();
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/academic/curriculum', [
                'subject_id' => $subject->id,
                'academic_year' => '2026',
                'syllabus' => 'Algebra fundamentals',
                'learning_outcomes' => [['code' => 'LO1', 'description' => 'Solve equations']],
            ])->assertCreated();

        $category = AssessmentCategory::create([
            'school_id' => $auth['school']->id,
            'subject_id' => $subject->id,
            'name' => 'CA1',
            'type' => 'ca',
            'weight' => 40,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/academic/gradebook-rules', [
                'subject_id' => $subject->id,
                'category_weights' => [(string) $category->id => 40, 'exam' => 60],
                'pass_mark' => 50,
            ])->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/academic/continuous-assessments', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'category_id' => $category->id,
                'title' => 'Test 1',
                'score' => 80,
                'max_score' => 100,
            ])->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson("/api/v1/enterprise/academic/students/{$student->id}/subjects/{$subject->id}/weighted-grade")
            ->assertOk()
            ->assertJsonStructure(['data' => ['weighted_percent', 'pass']]);
    }

    public function test_double_entry_journal_and_financial_reports(): void
    {
        $auth = $this->createAuthenticatedUser();
        $finance = app(FinanceEnterpriseService::class);
        $finance->seedDefaultAccounts($auth['school']->id);

        $cash = ChartOfAccount::where('school_id', $auth['school']->id)->where('code', '1000')->first();
        $revenue = ChartOfAccount::where('school_id', $auth['school']->id)->where('code', '4000')->first();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/finance/journals', [
                'description' => 'Tuition receipt',
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000],
                ],
            ])->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/enterprise/finance/reports/profit-loss')
            ->assertOk()
            ->assertJsonStructure(['data' => ['revenue', 'expenses', 'net_income']]);
    }

    public function test_question_bank_cbt_and_remark_request(): void
    {
        $auth = $this->createAuthenticatedUser();
        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);

        $question = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/exams/question-bank', [
                'subject_id' => $subject->id,
                'question_text' => 'What is 2+2?',
                'correct_answer' => '4',
                'difficulty' => 'easy',
                'marks' => 5,
            ])->assertCreated();

        $qid = $question->json('data.id');

        $session = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/exams/cbt/start', [
                'student_id' => $student->id,
                'question_ids' => [$qid],
            ])->assertCreated();

        $sessionId = $session->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/enterprise/exams/cbt/{$sessionId}/submit", [
                'answers' => [['question_id' => $qid, 'answer' => '4']],
            ])->assertOk()
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_command_center_and_admission_scoring(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/admissions/score', [
                'applicant_name' => 'Jane Doe',
                'academic_score' => 85,
                'interview_score' => 80,
            ])->assertCreated()
            ->assertJsonPath('data.recommendation', 'strong_accept');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/enterprise/command-center')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'school_health', 'financial_status', 'academic_heatmap',
                    'approval_queue', 'risk_alerts', 'kpi_scorecard',
                ],
            ]);
    }

    public function test_hr_governance_and_group_management(): void
    {
        $auth = $this->createAuthenticatedUser();
        $teacher = User::factory()->create(['school_id' => $auth['school']->id, 'role' => 'teacher']);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/hr/performance-reviews', [
                'staff_user_id' => $teacher->id,
                'period' => '2026-T1',
                'overall_score' => 4.2,
                'summary' => 'Strong classroom delivery',
            ])->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/group/policies', [
                'policy_code' => 'FEE_POLICY',
                'name' => 'Standard fee policy',
                'rules' => ['late_fee_percent' => 5],
            ])->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/integrations/connectors', [
                'provider' => 'moodle',
                'connector_type' => 'lms',
                'config' => ['url' => 'https://lms.example.com'],
                'is_active' => true,
            ])->assertOk();
    }
}
