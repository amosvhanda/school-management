<?php

namespace Tests\Feature;

use App\Models\QuestionBankItem;
use App\Models\Subject;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class StudentPortalCbtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_student_can_start_and_submit_cbt_session(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        $student = \App\Models\Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
            'status' => 'active',
        ]);

        $subject = Subject::factory()->create(['school_id' => $auth['school']->id]);

        foreach (range(1, 3) as $i) {
            QuestionBankItem::query()->create([
                'school_id' => $auth['school']->id,
                'subject_id' => $subject->id,
                'question_text' => "Question {$i}?",
                'question_type' => 'mcq',
                'options' => ['A', 'B'],
                'correct_answer' => 'A',
                'marks' => 1,
            ]);
        }

        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)
            ->getJson('/api/v1/student-portal/cbt/available')
            ->assertOk()
            ->assertJsonPath('data.subjects.0.subject_id', $subject->id);

        $start = $this->withHeaders($headers)
            ->postJson('/api/v1/student-portal/cbt/start', [
                'subject_id' => $subject->id,
                'count' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress');

        $sessionId = $start->json('data.id');
        $questions = $start->json('data.questions');
        $this->assertNotEmpty($questions);
        $this->assertArrayNotHasKey('correct_answer', $questions[0]);

        $answers = array_map(fn ($q) => [
            'question_id' => $q['id'],
            'answer' => 'A',
        ], $questions);

        $this->withHeaders($headers)
            ->postJson('/api/v1/student-portal/cbt/sessions/'.$sessionId.'/submit', [
                'answers' => $answers,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('cbt_exam_sessions', [
            'id' => $sessionId,
            'student_id' => $student->id,
            'status' => 'submitted',
        ]);
    }
}
