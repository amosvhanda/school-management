<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\School;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    public function test_hr_staff_can_create_job_and_record_application(): void
    {
        $auth = $this->createAuthenticatedUser();

        $jobResponse = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/recruitment/jobs', [
                'title' => 'Mathematics Teacher',
                'department' => 'Science',
                'employment_type' => 'full_time',
                'status' => 'open',
                'openings' => 2,
            ]);

        $jobResponse->assertCreated()
            ->assertJsonPath('data.title', 'Mathematics Teacher')
            ->assertJsonPath('data.status', 'open');

        $jobId = $jobResponse->json('data.id');

        $applicationResponse = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/recruitment/applications', [
                'job_posting_id' => $jobId,
                'applicant_name' => 'Tendai Nyathi',
                'email' => 'tendai@example.com',
                'phone' => '+263771234567',
            ]);

        $applicationResponse->assertCreated()
            ->assertJsonPath('data.applicant_name', 'Tendai Nyathi')
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('job_applications', [
            'school_id' => $auth['school']->id,
            'job_posting_id' => $jobId,
            'applicant_name' => 'Tendai Nyathi',
        ]);
    }

    public function test_hr_staff_can_advance_application_to_hire(): void
    {
        $auth = $this->createAuthenticatedUser();

        $job = JobPosting::create([
            'school_id' => $auth['school']->id,
            'title' => 'School Secretary',
            'status' => 'open',
            'openings' => 1,
        ]);

        $application = JobApplication::create([
            'school_id' => $auth['school']->id,
            'job_posting_id' => $job->id,
            'applicant_name' => 'Rudo M',
            'status' => 'submitted',
        ]);

        $token = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($token)->postJson("/api/v1/recruitment/applications/{$application->id}/shortlist")->assertOk();
        $this->withHeaders($token)->postJson("/api/v1/recruitment/applications/{$application->id}/interview")->assertOk();
        $this->withHeaders($token)->postJson("/api/v1/recruitment/applications/{$application->id}/offer")->assertOk();
        $this->withHeaders($token)->postJson("/api/v1/recruitment/applications/{$application->id}/hire")->assertOk()
            ->assertJsonPath('data.status', 'hired');

        $this->assertDatabaseHas('job_applications', ['id' => $application->id, 'status' => 'hired']);
    }

    public function test_staff_cannot_manage_recruitment_for_other_school(): void
    {
        $auth = $this->createAuthenticatedUser();
        $otherSchool = School::factory()->create();

        $job = JobPosting::create([
            'school_id' => $otherSchool->id,
            'title' => 'Other school role',
            'status' => 'open',
            'openings' => 1,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/recruitment/jobs/{$job->id}", ['title' => 'Hacked'])
            ->assertNotFound();
    }
}
