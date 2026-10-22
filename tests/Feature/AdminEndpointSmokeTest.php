<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Broad admin smoke matrix — list/dashboard endpoints must not 403/500 for a school admin.
 */
class AdminEndpointSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_core_module_endpoints(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'role' => 'admin',
        ]);
        Sanctum::actingAs($admin);

        $getOk = [
            '/api/v1/auth/me',
            '/api/v1/dashboard/kpis',
            '/api/v1/dashboard/school-widgets',
            '/api/v1/dashboard/lms-widgets',
            '/api/v1/dashboard/role-preview/student',
            '/api/v1/dashboard/role-preview/teacher',
            '/api/v1/dashboard/role-preview/parent',
            '/api/v1/dashboard/activity',
            '/api/v1/dashboard/recent-activity',
            '/api/v1/teacher-portal/online-lessons',
            '/api/v1/students?limit=5',
            '/api/v1/teachers?limit=5',
            '/api/v1/guardians?limit=5',
            '/api/v1/classes?limit=5',
            '/api/v1/subjects?limit=5',
            '/api/v1/streams?limit=5',
            '/api/v1/rooms?limit=5',
            '/api/v1/terms?limit=5',
            '/api/v1/exams?limit=5',
            '/api/v1/exam-schedules?limit=5',
            '/api/v1/assignments?limit=5',
            '/api/v1/tests?limit=5',
            '/api/v1/timetable?limit=5',
            '/api/v1/teacher-assignments',
            '/api/v1/attendance?limit=5',
            '/api/v1/fee-categories?limit=5',
            '/api/v1/fee-groups?limit=5',
            '/api/v1/fee-discounts?limit=5',
            '/api/v1/payments?limit=5',
            '/api/v1/invoices?limit=5',
            '/api/v1/transactions?limit=5',
            '/api/v1/income-heads?limit=5',
            '/api/v1/expense-heads?limit=5',
            '/api/v1/payroll',
            '/api/v1/employees?limit=5',
            '/api/v1/designations?limit=5',
            '/api/v1/departments?limit=5',
            '/api/v1/leave-types?limit=5',
            '/api/v1/leave-requests?limit=5',
            '/api/v1/staff-attendance?limit=5',
            '/api/v1/library/books?limit=5',
            '/api/v1/library/members?limit=5',
            '/api/v1/library/loans?limit=5',
            '/api/v1/announcements?limit=5',
            '/api/v1/events?limit=5',
            '/api/v1/communications/threads?limit=5',
            '/api/v1/transport/routes',
            '/api/v1/transport/vehicles',
            '/api/v1/transport/drivers',
            '/api/v1/hostels',
            '/api/v1/inventory/items',
            '/api/v1/roles?limit=5',
            '/api/v1/users?limit=5',
            '/api/v1/workflows/pending',
            '/api/v1/reports/templates',
            '/api/v1/settings/school',
        ];

        $failures = [];
        foreach ($getOk as $path) {
            $response = $this->getJson($path);
            $status = $response->status();
            if ($status >= 400) {
                $failures[] = "{$status} {$path} :: ".($response->json('message') ?? $response->content());
            }
        }

        $this->assertSame([], $failures, "Admin GET failures:\n".implode("\n", $failures));
    }
}
