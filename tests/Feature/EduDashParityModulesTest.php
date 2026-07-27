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
        ['school' => $school] = $this->actingAdmin();

        $usd = $this->postJson('/api/v1/school-currencies', [
            'code' => 'usd',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ])->assertCreated()->json('data');

        $this->assertTrue($usd['is_default']);
        $this->assertSame('USD', $school->fresh()->currency);

        $this->postJson('/api/v1/school-currencies', [
            'code' => 'ZWG',
            'name' => 'Zimbabwe Gold',
            'symbol' => 'ZiG',
            'is_default' => true,
        ])->assertCreated();

        $this->assertSame('ZWG', $school->fresh()->currency);

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

    public function test_library_member_can_link_to_student(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $student = \App\Models\Student::factory()->create(['school_id' => $school->id]);

        $this->postJson('/api/v1/library/members', [
            'member_type' => 'student',
            'member_id' => $student->id,
            'name' => $student->full_name,
            'member_number' => 'LIB-STU-1',
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.member_id', $student->id);
    }

    public function test_manual_income_transaction_can_be_recorded(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();

        $incomeHead = $this->postJson('/api/v1/income-heads', [
            'name' => 'Donations',
            'code' => 'DON',
        ])->assertCreated()->json('data');

        $transaction = $this->postJson('/api/v1/transactions', [
            'type' => 'income',
            'description' => 'Alumni donation',
            'amount' => 250,
            'income_head_id' => $incomeHead['id'],
            'payment_method' => 'bank_transfer',
            'currency' => 'USD',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.amount', '250.00')
            ->json('data');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction['id'],
            'school_id' => $school->id,
            'type' => 'income',
            'income_head_id' => $incomeHead['id'],
            'credit' => 250,
            'debit' => 0,
            'status' => 'completed',
            'created_by' => $user->id,
        ]);
    }

    public function test_system_generated_transaction_cannot_be_deleted(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $invoice = \App\Models\Invoice::factory()->create(['school_id' => $school->id]);

        $transaction = \App\Models\Transaction::create([
            'school_id' => $school->id,
            'invoice_id' => $invoice->id,
            'type' => 'income',
            'category' => 'fees',
            'description' => 'Invoice payment',
            'credit' => 100,
            'debit' => 0,
            'balance' => 100,
            'currency' => 'USD',
            'status' => 'completed',
        ]);

        $this->deleteJson('/api/v1/transactions/'.$transaction->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_staff_can_compose_a_message_thread_to_a_parent(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();

        $parent = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'parent',
        ]);

        $response = $this->postJson('/api/v1/communications/threads', [
            'subject' => 'Attendance follow-up',
            'message' => 'Please confirm the reason for absence.',
            'parent_user_id' => $parent->id,
        ])->assertCreated()->json('data');

        $this->assertDatabaseHas('communication_threads', [
            'id' => $response['id'],
            'school_id' => $school->id,
            'parent_user_id' => $parent->id,
            'staff_user_id' => $user->id,
            'subject' => 'Attendance follow-up',
        ]);

        $this->assertDatabaseHas('communication_messages', [
            'thread_id' => $response['id'],
            'sender_id' => $user->id,
            'body' => 'Please confirm the reason for absence.',
        ]);
    }

    public function test_staff_cannot_compose_thread_for_non_parent_user(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $notParent = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'teacher',
        ]);

        $this->postJson('/api/v1/communications/threads', [
            'subject' => 'Invalid',
            'message' => 'Should fail',
            'parent_user_id' => $notParent->id,
        ])->assertStatus(422);
    }

    public function test_school_event_can_be_deleted(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();

        $event = \App\Models\SchoolEvent::create([
            'school_id' => $school->id,
            'title' => 'Prize giving',
            'type' => 'ceremony',
            'starts_at' => now()->addWeek(),
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);

        $this->deleteJson('/api/v1/events/'.$event->id)
            ->assertOk()
            ->assertJsonPath('message', 'Event deleted');

        $this->assertDatabaseMissing('school_events', ['id' => $event->id]);
    }

    public function test_school_dashboard_widgets_and_kpi_parity_fields(): void
    {
        ['school' => $school, 'user' => $user] = $this->actingAdmin();

        \App\Models\Student::factory()->create(['school_id' => $school->id]);
        \App\Models\Teacher::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        \App\Models\Announcement::create([
            'school_id' => $school->id,
            'title' => 'Term opening',
            'message' => 'Welcome back',
            'type' => 'general',
            'target_audience' => 'all',
            'date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        \App\Models\SchoolEvent::create([
            'school_id' => $school->id,
            'title' => 'Sports day',
            'type' => 'sports',
            'starts_at' => now()->addDays(3),
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);

        $this->getJson('/api/v1/dashboard/kpis')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totalStudents',
                    'totalTeachers',
                    'totalStaff',
                    'totalParents',
                    'collectedThisMonth',
                    'incomeThisMonth',
                    'expenseThisMonth',
                    'newAdmissionsThisMonth',
                ],
            ]);

        $this->getJson('/api/v1/dashboard/school-widgets')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'notices',
                    'leave_requests',
                    'upcoming_events',
                    'top_teachers',
                    'top_students',
                    'new_admissions',
                    'charts' => [
                        'fee_revenue',
                        'income_expense',
                        'admissions_by_class',
                        'calendar_events',
                    ],
                ],
            ])
            ->assertJsonPath('data.notices.0.title', 'Term opening')
            ->assertJsonPath('data.upcoming_events.0.title', 'Sports day')
            ->assertJsonCount(12, 'data.charts.fee_revenue')
            ->assertJsonCount(12, 'data.charts.income_expense');
    }

    public function test_guardian_without_students_can_be_deleted(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $guardian = \App\Models\Guardian::factory()->create(['school_id' => $school->id]);

        $this->deleteJson('/api/v1/guardians/'.$guardian->id)
            ->assertOk();

        $this->assertDatabaseMissing('guardians', ['id' => $guardian->id]);
    }

    public function test_library_book_with_active_loan_cannot_be_deleted(): void
    {
        ['school' => $school] = $this->actingAdmin();
        $book = \App\Models\LibraryBook::create([
            'school_id' => $school->id,
            'title' => 'Things Fall Apart',
            'total_copies' => 1,
            'available_copies' => 0,
        ]);
        $member = \App\Models\LibraryMember::create([
            'school_id' => $school->id,
            'member_type' => 'external',
            'name' => 'Reader',
            'member_number' => 'LIB-X-1',
            'status' => 'active',
        ]);
        \App\Models\LibraryLoan::create([
            'book_id' => $book->id,
            'library_member_id' => $member->id,
            'borrowed_at' => now(),
            'due_at' => now()->addWeek(),
            'status' => 'borrowed',
        ]);

        $this->deleteJson('/api/v1/library/books/'.$book->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('library_books', ['id' => $book->id]);
    }

    public function test_admin_can_manage_school_lms_without_teacher_profile(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $teacher = \App\Models\Teacher::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/teacher-portal/online-lessons')
            ->assertOk()
            ->assertJsonPath('data', []);

        $this->postJson('/api/v1/teacher-portal/online-lessons', [
            'title' => 'Form 3 Science Live',
            'lesson_type' => 'live',
            'teacher_id' => $teacher->id,
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Form 3 Science Live')
            ->assertJsonPath('data.teacher_id', $teacher->id);

        $this->getJson('/api/v1/teacher-portal/online-lessons')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Form 3 Science Live');
    }

    public function test_lms_and_role_preview_dashboard_endpoints(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $teacher = \App\Models\Teacher::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        \App\Models\OnlineLesson::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'title' => 'LMS Orientation',
            'lesson_type' => 'live',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $this->getJson('/api/v1/dashboard/lms-widgets')
            ->assertOk()
            ->assertJsonPath('data.kpis.total_lessons', 1)
            ->assertJsonPath('data.upcoming_sessions.0.title', 'LMS Orientation');

        $this->getJson('/api/v1/dashboard/role-preview/student')
            ->assertOk()
            ->assertJsonPath('data.role', 'student')
            ->assertJsonStructure(['data' => ['kpis', 'highlights', 'notices', 'upcoming_events']]);

        $this->getJson('/api/v1/dashboard/role-preview/teacher')
            ->assertOk()
            ->assertJsonPath('data.role', 'teacher');

        $this->getJson('/api/v1/dashboard/role-preview/parent')
            ->assertOk()
            ->assertJsonPath('data.role', 'parent');
    }

    public function test_admin_without_teacher_is_forbidden_from_teacher_only_portal(): void
    {
        $this->actingAdmin();

        foreach ([
            '/api/v1/teacher-portal/classes',
            '/api/v1/teacher-portal/lesson-plans',
            '/api/v1/teacher-portal/dashboard',
            '/api/v1/teacher-portal/notifications',
        ] as $path) {
            $this->getJson($path)
                ->assertForbidden()
                ->assertJsonFragment(['message' => 'Teacher profile not linked to this account.']);
        }
    }

    public function test_admin_can_submit_and_lock_attendance_without_teacher_profile(): void
    {
        ['school' => $school] = $this->actingAdmin();

        $class = \App\Models\ClassModel::factory()->create([
            'school_id' => $school->id,
        ]);

        $this->postJson('/api/v1/teacher-portal/attendance/submit', [
            'class_id' => $class->id,
            'date' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Attendance submitted');

        $this->postJson('/api/v1/teacher-portal/attendance/lock', [
            'class_id' => $class->id,
            'date' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Attendance locked');

        $this->getJson('/api/v1/teacher-portal/attendance/reports?'.http_build_query([
            'class_id' => $class->id,
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]))
            ->assertOk();
    }
}
