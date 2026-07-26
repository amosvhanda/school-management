<?php

namespace Tests\Feature;

use App\Models\FeeCategory;
use App\Models\FeeGroup;
use App\Models\LeaveType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EduDashParityModulesTest extends TestCase
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

    public function test_fee_group_crud_and_category_sync(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $category = FeeCategory::create([
            'school_id' => $school->id,
            'name' => 'Tuition',
            'is_active' => true,
            'order' => 0,
        ]);

        $create = $this->postJson('/api/v1/fee-groups', [
            'name' => 'Term 1 bundle',
            'description' => 'Core fees',
            'fee_category_ids' => [$category->id],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Term 1 bundle');

        $groupId = $create->json('data.id');
        $this->assertDatabaseHas('fee_group_items', [
            'fee_group_id' => $groupId,
            'fee_category_id' => $category->id,
        ]);

        $this->getJson('/api/v1/fee-groups')
            ->assertOk()
            ->assertJsonPath('data.0.categories_count', 1);
    }

    public function test_leave_type_and_leave_request_with_type_id(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();

        $leaveType = LeaveType::create([
            'school_id' => $school->id,
            'name' => 'Annual leave',
            'code' => 'annual',
            'is_paid' => true,
            'is_active' => true,
        ]);

        $teacher = \App\Models\Teacher::factory()->create(['school_id' => $school->id]);

        $this->postJson('/api/v1/leave-requests', [
            'teacher_id' => $teacher->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason' => 'Family trip',
        ])->assertCreated()
            ->assertJsonPath('data.leave_type_id', $leaveType->id);

        $this->assertDatabaseHas('leave_requests', [
            'teacher_id' => $teacher->id,
            'leave_type_id' => $leaveType->id,
            'type' => 'annual',
            'requested_by' => $user->id,
        ]);
    }

    public function test_employee_and_staff_attendance_roster(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $employee = $this->postJson('/api/v1/employees', [
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'employee_number' => 'EMP-100',
            'status' => 'active',
        ])->assertCreated()
            ->json('data');

        $this->assertSame('Tendai Moyo', $employee['name']);

        $roster = $this->getJson('/api/v1/staff-attendance/roster?date='.now()->toDateString())
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($roster['employees']);

        $this->postJson('/api/v1/staff-attendance', [
            'date' => now()->toDateString(),
            'entries' => [[
                'staff_type' => 'employee',
                'staff_id' => $employee['id'],
                'status' => 'present',
            ]],
        ])->assertCreated();

        $this->assertDatabaseHas('staff_attendances', [
            'school_id' => $school->id,
            'staff_type' => 'employee',
            'staff_id' => $employee['id'],
            'status' => 'present',
        ]);
    }

    public function test_certificate_template_issue(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $student = \App\Models\Student::factory()->create(['school_id' => $school->id]);

        $template = $this->postJson('/api/v1/certificate-templates', [
            'name' => 'Character',
            'certificate_type' => 'character',
            'title' => 'Certificate of Character',
            'body_html' => '<p>{{student_name}} — {{school_name}} on {{date}}</p>',
        ])->assertCreated()->json('data');

        $issued = $this->postJson('/api/v1/certificate-templates/'.$template['id'].'/issue', [
            'student_id' => $student->id,
        ])->assertCreated()->json('data');

        $this->assertNotEmpty($issued['verification_code']);
        $this->assertStringContainsString($student->full_name, $issued['content_html']);
    }

    public function test_income_and_expense_heads_crud(): void
    {
        $this->actingAdmin();

        $income = $this->postJson('/api/v1/income-heads', [
            'name' => 'Donations',
            'code' => 'DON',
        ])->assertCreated()->json('data');

        $this->assertSame('Donations', $income['name']);

        $expense = $this->postJson('/api/v1/expense-heads', [
            'name' => 'Utilities',
            'code' => 'UTIL',
        ])->assertCreated()->json('data');

        $this->putJson('/api/v1/expense-heads/'.$expense['id'], [
            'name' => 'Utilities & rates',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Utilities & rates');

        $this->getJson('/api/v1/income-heads')->assertOk();
        $this->getJson('/api/v1/expense-heads')->assertOk();
    }

    public function test_exam_schedule_crud(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $exam = \App\Models\Exam::factory()->create(['school_id' => $school->id]);

        $created = $this->postJson('/api/v1/exam-schedules', [
            'exam_id' => $exam->id,
            'starts_at' => now()->addWeek()->toIso8601String(),
            'duration_minutes' => 90,
            'invigilator' => 'Mrs Ncube',
        ])->assertCreated()->json('data');

        $this->assertSame($exam->id, $created['exam_id']);
        $this->assertSame(90, $created['duration_minutes']);

        $this->getJson('/api/v1/exam-schedules')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);
    }

    public function test_library_member_crud(): void
    {
        $this->actingAdmin();

        $member = $this->postJson('/api/v1/library/members', [
            'member_type' => 'external',
            'name' => 'Community Reader',
            'member_number' => 'LIB-001',
            'status' => 'active',
        ])->assertCreated()->json('data');

        $this->assertSame('Community Reader', $member['name']);

        $this->putJson('/api/v1/library/members/'.$member['id'], [
            'status' => 'suspended',
        ])->assertOk()
            ->assertJsonPath('data.status', 'suspended');
    }

    public function test_school_currency_and_language_defaults(): void
    {
        $this->actingAdmin();

        $usd = $this->postJson('/api/v1/school-currencies', [
            'code' => 'usd',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ])->assertCreated()->json('data');

        $this->assertTrue($usd['is_default']);

        $this->postJson('/api/v1/school-currencies', [
            'code' => 'ZWG',
            'name' => 'Zimbabwe Gold',
            'symbol' => 'ZiG',
            'is_default' => true,
        ])->assertCreated();

        $this->getJson('/api/v1/school-currencies')
            ->assertOk();

        $en = $this->postJson('/api/v1/school-languages', [
            'code' => 'EN',
            'name' => 'English',
            'is_default' => true,
        ])->assertCreated()->json('data');

        $this->assertTrue($en['is_default']);

        $sn = $this->postJson('/api/v1/school-languages', [
            'code' => 'sn',
            'name' => 'Shona',
            'is_default' => true,
        ])->assertCreated()->json('data');

        $this->assertTrue($sn['is_default']);

        $this->getJson('/api/v1/school-languages/'.$en['id'])
            ->assertOk()
            ->assertJsonPath('data.is_default', false);
    }
}
