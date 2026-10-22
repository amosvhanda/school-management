<?php

namespace Tests\Feature;

use App\Models\AcademicCalendarEntry;
use App\Models\AlumniRecord;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\Teacher;
use Tests\TestCase;

class AlumniBudgetCalendarModulesTest extends TestCase
{
    public function test_alumni_crud_and_engagement_are_school_scoped(): void
    {
        $auth = $this->createAuthenticatedUser();

        $create = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/alumni', [
                'full_name' => 'Jane Graduate',
                'graduation_year' => 2024,
                'email' => 'jane@example.com',
                'phone' => '0771000000',
                'current_occupation' => 'Engineer',
            ])
            ->assertCreated();

        $id = $create->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/enterprise/alumni/{$id}", [
                'phone' => '0771111111',
                'current_occupation' => 'Senior Engineer',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '0771111111');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/enterprise/alumni/{$id}/engagements", [
                'type' => 'call',
                'note' => 'Discussed reunion attendance',
            ])
            ->assertCreated()
            ->assertJsonPath('data.engagement_history.0.type', 'call');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/enterprise/alumni')
            ->assertOk()
            ->assertJsonFragment(['full_name' => 'Jane Graduate']);

        $other = $this->createAuthenticatedUser();
        $this->assertNotEquals($auth['school']->id, $other['school']->id);

        // Ensure Sanctum auth from the previous request does not leak into the next one.
        $this->app['auth']->forgetGuards();

        $list = $this->withHeaders(['Authorization' => 'Bearer '.$other['token']])
            ->getJson('/api/v1/enterprise/alumni')
            ->assertOk()
            ->json('data');

        $this->assertFalse(
            collect($list)->contains(fn ($row) => ($row['full_name'] ?? null) === 'Jane Graduate')
        );
    }

    public function test_register_alumni_from_student_is_idempotent(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'full_name' => 'Grad Student',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/enterprise/alumni/students/{$student->id}", [
                'graduation_year' => 2025,
                'email' => 'grad@example.com',
            ])
            ->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/enterprise/alumni/students/{$student->id}", [
                'graduation_year' => 2025,
                'email' => 'grad-updated@example.com',
            ])
            ->assertCreated();

        $this->assertEquals(1, AlumniRecord::where('student_id', $student->id)->count());
        $this->assertEquals('grad-updated@example.com', AlumniRecord::where('student_id', $student->id)->value('email'));
    }

    public function test_budget_workflow_submit_approve_and_spend(): void
    {
        $auth = $this->createAuthenticatedUser();

        $budgetId = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/budgets', [
                'name' => 'Lab equipment',
                'fiscal_year' => '2026',
                'department' => 'Science',
                'allocated_amount' => 1000,
                'currency' => 'USD',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/budgets/{$budgetId}/spend", ['amount' => 50])
            ->assertStatus(422);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/budgets/{$budgetId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/budgets/{$budgetId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/budgets/{$budgetId}/spend", [
                'amount' => 250,
                'note' => 'Microscopes',
            ])
            ->assertOk()
            ->assertJsonPath('data.spent_amount', '250.00');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/budgets/{$budgetId}/spend", ['amount' => 800])
            ->assertStatus(422);

        $this->assertDatabaseHas('budgets', [
            'id' => $budgetId,
            'status' => 'approved',
            'school_id' => $auth['school']->id,
        ]);
    }

    public function test_academic_calendar_update_and_delete(): void
    {
        $auth = $this->createAuthenticatedUser();

        $id = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/academic/calendar', [
                'entry_type' => 'holiday',
                'title' => 'Independence Day',
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'is_holiday' => true,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->putJson("/api/v1/enterprise/academic/calendar/{$id}", [
                'title' => 'National Holiday',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'National Holiday');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->deleteJson("/api/v1/enterprise/academic/calendar/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('academic_calendar_entries', ['id' => $id]);
    }

    public function test_teacher_calendar_includes_events_and_academic_entries(): void
    {
        $auth = $this->createAuthenticatedUser('teacher');
        Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'user_id' => $auth['user']->id,
        ]);

        SchoolEvent::create([
            'school_id' => $auth['school']->id,
            'title' => 'Sports Day',
            'type' => 'sports',
            'starts_at' => now()->addDays(2),
            'status' => 'scheduled',
            'created_by' => $auth['user']->id,
        ]);

        AcademicCalendarEntry::create([
            'school_id' => $auth['school']->id,
            'entry_type' => 'holiday',
            'title' => 'Mid-term break',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_holiday' => true,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/teacher-portal/calendar')
            ->assertOk();

        $this->assertNotEmpty($response->json('data.events'));
        $this->assertNotEmpty($response->json('data.academic_entries'));
        $this->assertEquals('Sports Day', $response->json('data.events.0.title'));
        $this->assertNotEmpty($response->json('data.events.0.starts_at'));
    }
}
