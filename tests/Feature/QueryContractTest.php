<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\GradeLevel;
use App\Models\Subject;
use Tests\TestCase;

class QueryContractTest extends TestCase
{
    private function authHeaders(array $auth): array
    {
        return ['Authorization' => 'Bearer '.$auth['token']];
    }

    public function test_students_filter_sort_include_and_pagination_meta(): void
    {
        $auth = $this->createAuthenticatedUser();
        $schoolId = $auth['school']->id;

        $active = Student::factory()->create([
            'school_id' => $schoolId,
            'status' => 'active',
            'full_name' => 'Alpha Active',
            'first_name' => 'Alpha',
            'last_name' => 'Active',
        ]);
        Student::factory()->create([
            'school_id' => $schoolId,
            'status' => 'inactive',
            'full_name' => 'Beta Inactive',
        ]);

        $guardian = Guardian::factory()->create(['school_id' => $schoolId]);
        $active->guardians()->attach($guardian->id, [
            'relationship' => 'parent',
            'is_primary' => true,
            'can_pickup' => true,
            'emergency_contact' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/students?filter[status]=active&sort=full_name&include=guardians&per_page=10&page=1');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonStructure([
                'data' => [['id', 'full_name', 'status']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
            ]);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertCount(1, $ids);
        $this->assertNotEmpty($response->json('data.0.guardians'));
    }

    public function test_students_reject_unknown_filter_and_sort(): void
    {
        $auth = $this->createAuthenticatedUser();

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/students?filter[not_a_real_field]=x')
            ->assertStatus(422);

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/students?sort=password')
            ->assertStatus(422);
    }

    public function test_students_per_page_cap(): void
    {
        $auth = $this->createAuthenticatedUser();
        Student::factory()->count(3)->create(['school_id' => $auth['school']->id]);

        $response = $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/students?per_page=999');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_students_legacy_search_and_all_shim(): void
    {
        $auth = $this->createAuthenticatedUser();
        $match = Student::factory()->create([
            'school_id' => $auth['school']->id,
            'full_name' => 'Tino Legacy',
            'first_name' => 'Tino',
            'last_name' => 'Legacy',
        ]);
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'full_name' => 'Other Student',
        ]);

        $response = $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/students?search=Tino&all=true');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($match->id, $response->json('data.0.id'));
    }

    public function test_students_sparse_fieldset(): void
    {
        $auth = $this->createAuthenticatedUser();
        Student::factory()->create([
            'school_id' => $auth['school']->id,
            'full_name' => 'Sparse Student',
        ]);

        $response = $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/students?fields[students]=id,full_name,student_number&all=true');

        $response->assertOk();
        $row = $response->json('data.0');
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('full_name', $row);
        $this->assertArrayHasKey('student_number', $row);
    }

    public function test_students_include_guardians_avoids_n_plus_one(): void
    {
        $auth = $this->createAuthenticatedUser();
        $schoolId = $auth['school']->id;

        $students = Student::factory()->count(8)->create(['school_id' => $schoolId]);
        foreach ($students as $student) {
            $guardian = Guardian::factory()->create(['school_id' => $schoolId]);
            $student->guardians()->attach($guardian->id, [
                'relationship' => 'parent',
                'is_primary' => true,
                'can_pickup' => true,
                'emergency_contact' => false,
            ]);
        }

        \Illuminate\Database\Eloquent\Model::preventLazyLoading();

        try {
            $response = $this->withHeaders($this->authHeaders($auth))
                ->getJson('/api/v1/students?include=guardians&per_page=8&page=1');

            $response->assertOk()->assertJsonCount(8, 'data');
            foreach ($response->json('data') as $row) {
                $this->assertArrayHasKey('guardians', $row);
                $this->assertNotEmpty($row['guardians']);
            }
        } finally {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
        }
    }

    public function test_teachers_query_contract(): void
    {
        $auth = $this->createAuthenticatedUser();
        Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'active',
            'name' => 'Ada Teacher',
            'department' => 'Science',
        ]);
        Teacher::factory()->create([
            'school_id' => $auth['school']->id,
            'status' => 'inactive',
            'name' => 'Inactive One',
        ]);

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/teachers?filter[status]=active&filter[search]=Ada&sort=name&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/teachers?filter[hack]=1')
            ->assertStatus(422);
    }

    public function test_invoices_and_payments_query_contract(): void
    {
        $auth = $this->createAuthenticatedUser();
        $schoolId = $auth['school']->id;
        $student = Student::factory()->create(['school_id' => $schoolId, 'full_name' => 'Payee Student']);

        $invoice = Invoice::factory()->create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'status' => 'pending',
            'invoice_number' => 'INV-QC-1',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
        Invoice::factory()->create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'status' => 'paid',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        Payment::factory()->create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'status' => 'completed',
            'method' => 'cash',
            'reference' => 'REF-QC-1',
        ]);

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/invoices?filter[status]=pending&include=student&sort=-created_at&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice_number', 'INV-QC-1');

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/payments?filter[method]=cash&include=student,invoice&sort=-date&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/invoices?sort=secret_column')
            ->assertStatus(422);
    }

    public function test_attendance_and_exams_query_contract(): void
    {
        $auth = $this->createAuthenticatedUser();
        $schoolId = $auth['school']->id;
        $class = ClassModel::factory()->create(['school_id' => $schoolId]);
        $student = Student::factory()->create(['school_id' => $schoolId, 'class_id' => $class->id]);

        Attendance::factory()->create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => '2026-07-01',
            'status' => 'present',
        ]);
        Attendance::factory()->create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => '2026-07-02',
            'status' => 'absent',
        ]);

        $term = Term::factory()->create(['school_id' => $schoolId]);
        $grade = GradeLevel::factory()->create(['school_id' => $schoolId]);
        $subject = Subject::factory()->create(['school_id' => $schoolId]);

        Exam::factory()->create([
            'school_id' => $schoolId,
            'term_id' => $term->id,
            'grade_level_id' => $grade->id,
            'subject_id' => $subject->id,
            'name' => 'Midterm QC',
            'academic_year' => '2026',
        ]);

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/attendance?filter[status]=present&include=student,classModel&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/exams?filter[academic_year]=2026&include=term,gradeLevel,subject&sort=-exam_date&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Midterm QC');

        $this->withHeaders($this->authHeaders($auth))
            ->getJson('/api/v1/attendance?filter[unknown]=1')
            ->assertStatus(422);
    }

    public function test_edudash_list_endpoints_return_pagination_meta(): void
    {
        $auth = $this->createAuthenticatedUser();
        $headers = $this->authHeaders($auth);
        $schoolId = $auth['school']->id;

        \App\Models\FeeGroup::create([
            'school_id' => $schoolId,
            'name' => 'Tuition Pack',
            'is_active' => true,
            'order' => 1,
        ]);
        \App\Models\Employee::create([
            'school_id' => $schoolId,
            'first_name' => 'Clerk',
            'last_name' => 'One',
            'name' => 'Clerk One',
            'email' => 'clerk.one@example.com',
            'status' => 'active',
            'employment_type' => 'full_time',
        ]);
        Guardian::factory()->create([
            'school_id' => $schoolId,
            'first_name' => 'Pat',
            'last_name' => 'Pager',
        ]);
        \App\Models\LibraryBook::create([
            'school_id' => $schoolId,
            'title' => 'Paged Book',
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
        \App\Models\IncomeHead::create([
            'school_id' => $schoolId,
            'name' => 'Fees Income',
            'code' => 'INC-FEES',
            'is_active' => true,
        ]);

        foreach ([
            '/api/v1/fee-groups?per_page=10&page=1',
            '/api/v1/employees?per_page=10&page=1',
            '/api/v1/guardians?per_page=10&page=1',
            '/api/v1/library/books?per_page=10&page=1',
            '/api/v1/income-heads?per_page=10&page=1',
            '/api/v1/transactions?per_page=10&page=1',
            '/api/v1/fee-structures?per_page=10&page=1',
            '/api/v1/inventory/items?per_page=10&page=1',
            '/api/v1/procurement/requisitions?per_page=10&page=1',
            '/api/v1/leave-requests?per_page=10&page=1',
        ] as $url) {
            $this->withHeaders($headers)
                ->getJson($url)
                ->assertOk()
                ->assertJsonPath('meta.current_page', 1)
                ->assertJsonStructure([
                    'data',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ]);
        }
    }
}
