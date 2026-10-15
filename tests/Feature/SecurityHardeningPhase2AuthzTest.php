<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class SecurityHardeningPhase2AuthzTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_is_denied_ops_compliance_finance_and_platform_modules(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $forbiddenGets = [
            '/api/v1/hostels',
            '/api/v1/assets',
            '/api/v1/school-trips',
            '/api/v1/holiday-programs',
            '/api/v1/procurement/requisitions',
            '/api/v1/procurement/vendors',
            '/api/v1/compliance/policies',
            '/api/v1/compliance/incidents',
            '/api/v1/consent-forms',
            '/api/v1/transactions',
            '/api/v1/transactions/summary',
            '/api/v1/analytics/insights',
            '/api/v1/workflows/history',
            '/api/v1/platform/scholarships',
            '/api/v1/platform/payment-gateways',
            '/api/v1/platform/refunds',
            '/api/v1/platform/operations/live',
            '/api/v1/platform/policy-rules',
            '/api/v1/enterprise/finance/accounts',
            '/api/v1/enterprise/command-center',
            '/api/v1/enterprise/hr/contracts',
        ];

        foreach ($forbiddenGets as $uri) {
            $this->withHeaders($headers)->getJson($uri)->assertForbidden($uri);
        }

        $this->withHeaders($headers)
            ->postJson('/api/v1/classes', [
                'name' => 'Forbidden Class',
                'grade_level' => 'Form 1',
            ])
            ->assertForbidden();

        $this->withHeaders($headers)
            ->postJson('/api/v1/streams', [
                'name' => 'Forbidden Stream',
                'code' => 'FS1',
            ])
            ->assertForbidden();
    }

    public function test_teacher_retains_staff_module_access_they_are_seeded_for(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)->getJson('/api/v1/announcements')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/classes')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/streams')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/disciplinary-records')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/workflows/pending')->assertOk();
    }

    public function test_parent_cannot_list_staff_consent_forms_but_can_use_portal(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'parent');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)->getJson('/api/v1/consent-forms')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/parent/portal/consent-forms')->assertOk();
    }

    public function test_finance_can_access_transactions_and_platform_finance_but_not_hostels(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'finance');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)->getJson('/api/v1/transactions')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/platform/scholarships')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/platform/refunds')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/hostels')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/compliance/policies')->assertForbidden();
    }

    public function test_operations_permission_override_grants_hostel_access(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'teacher');
        $opsPermissionId = collect(config('permissions'))
            ->firstWhere('slug', 'operations.manage')['id'] ?? null;

        $this->assertNotNull($opsPermissionId);

        $auth['user']->update(['permission_ids' => [$opsPermissionId]]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/hostels')
            ->assertOk();
    }

    public function test_admin_can_access_gated_ops_and_platform_modules(): void
    {
        $auth = $this->createAuthenticatedUser(role: 'admin');
        $headers = ['Authorization' => 'Bearer '.$auth['token']];

        $this->withHeaders($headers)->getJson('/api/v1/hostels')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/procurement/vendors')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/consent-forms')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/platform/payment-gateways')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/enterprise/finance/accounts')->assertOk();
    }

    public function test_seeded_teacher_role_still_excludes_operations_and_compliance(): void
    {
        $role = Role::query()->where('slug', 'teacher')->firstOrFail();
        $slugs = collect(config('permissions'))
            ->whereIn('id', $role->permission_ids ?? [])
            ->pluck('slug')
            ->all();

        $this->assertNotContains('operations.manage', $slugs);
        $this->assertNotContains('compliance.manage', $slugs);
        $this->assertNotContains('academics.manage', $slugs);
        $this->assertNotContains('finance.manage', $slugs);
        $this->assertContains('communications.manage', $slugs);
        $this->assertContains('students.manage', $slugs);
    }
}
