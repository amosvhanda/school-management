<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentPortalAssignmentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    /**
     * @return array{auth: array<string, mixed>, student: Student, assignment: Assignment}
     */
    protected function studentWithAssignment(): array
    {
        $auth = $this->createAuthenticatedUser(role: 'student');
        $class = ClassModel::factory()->create(['school_id' => $auth['school']->id]);
        $teacher = Teacher::factory()->create(['school_id' => $auth['school']->id]);

        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
            'email' => $auth['user']->email,
            'status' => 'active',
            'class_id' => $class->id,
        ]);

        $assignment = Assignment::query()->create([
            'school_id' => $auth['school']->id,
            'title' => 'Essay Draft',
            'description' => 'Write about your community',
            'subject' => 'English',
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'due_date' => now()->addDays(5)->toDateString(),
            'total_marks' => 30,
            'status' => 'active',
        ]);

        return compact('auth', 'student', 'assignment');
    }

    public function test_student_can_submit_assignment_with_content(): void
    {
        ['auth' => $auth, 'assignment' => $assignment] = $this->studentWithAssignment();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/student-portal/assignments/'.$assignment->id.'/submit', [
                'content' => 'My essay about local history.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.submission.status', 'submitted')
            ->assertJsonPath('data.submission.content', 'My essay about local history.');

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'status' => 'submitted',
            'content' => 'My essay about local history.',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/assignments')
            ->assertOk()
            ->assertJsonPath('data.0.submission_status', 'submitted');
    }

    public function test_student_can_submit_assignment_with_file(): void
    {
        ['auth' => $auth, 'assignment' => $assignment] = $this->studentWithAssignment();

        $file = UploadedFile::fake()->create('essay.pdf', 100, 'application/pdf');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->post('/api/v1/student-portal/assignments/'.$assignment->id.'/submit', [
                'file' => $file,
            ])
            ->assertCreated()
            ->assertJsonPath('data.submission.status', 'submitted');

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->first();

        $this->assertNotNull($submission?->file_url);
        $this->assertSame('submitted', $submission?->status);
    }

    public function test_student_cannot_resubmit_after_submitted(): void
    {
        ['auth' => $auth, 'student' => $student, 'assignment' => $assignment] = $this->studentWithAssignment();

        AssignmentSubmission::query()->create([
            'school_id' => $auth['school']->id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'content' => 'First attempt',
            'submitted_at' => now(),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/student-portal/assignments/'.$assignment->id.'/submit', [
                'content' => 'Second attempt',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['assignment']);
    }

    public function test_student_can_resubmit_after_resubmit_requested(): void
    {
        ['auth' => $auth, 'student' => $student, 'assignment' => $assignment] = $this->studentWithAssignment();

        AssignmentSubmission::query()->create([
            'school_id' => $auth['school']->id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => 'resubmit',
            'content' => 'Needs improvement',
            'teacher_comment' => 'Add more detail',
            'submitted_at' => now()->subDay(),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/student-portal/assignments/'.$assignment->id.'/submit', [
                'content' => 'Revised essay with more detail.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.submission.status', 'submitted')
            ->assertJsonPath('data.submission.content', 'Revised essay with more detail.')
            ->assertJsonPath('data.submission.teacher_comment', null);
    }

    public function test_show_assignment_includes_submission(): void
    {
        ['auth' => $auth, 'student' => $student, 'assignment' => $assignment] = $this->studentWithAssignment();

        AssignmentSubmission::query()->create([
            'school_id' => $auth['school']->id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => 'graded',
            'content' => 'Done',
            'score' => 27.5,
            'teacher_comment' => 'Well written',
            'submitted_at' => now()->subDay(),
            'graded_at' => now(),
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/student-portal/assignments/'.$assignment->id)
            ->assertOk()
            ->assertJsonPath('data.assignment.title', 'Essay Draft')
            ->assertJsonPath('data.submission.status', 'graded')
            ->assertJsonPath('data.submission.score', '27.50');
    }
}
