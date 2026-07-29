<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PaymentGatewayConfig;
use App\Models\Student;
use App\Services\Platform\PaymentGatewayService;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_payment_gateway_initiate_is_disabled_by_default(): void
    {
        config(['payments.gateway_live' => false]);

        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $auth['school']->id,
            'student_id' => $student->id,
        ]);

        PaymentGatewayConfig::create([
            'school_id' => $auth['school']->id,
            'provider' => 'stripe',
            'credentials' => ['api_key' => 'test'],
            'is_active' => true,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/platform/payments/initiate', [
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'amount' => 50,
                'payment_method' => 'mobile_money',
                'provider' => 'stripe',
            ])
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'payment_gateway_disabled');
    }

    public function test_payment_webhook_completion_requires_secret_when_live(): void
    {
        config([
            'payments.gateway_live' => true,
            'payments.webhook_secret' => 'super-secret',
        ]);

        $this->expectException(\App\Exceptions\DomainException::class);

        app(PaymentGatewayService::class)->completeWebhook('PG-MISSING', ['reference' => 'x'], 'wrong');
    }

    public function test_parent_cannot_access_dashboard_kpis(): void
    {
        $this->seed(RoleSeeder::class);
        $auth = $this->createAuthenticatedUser(role: 'parent');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/dashboard/kpis')
            ->assertForbidden();
    }

    public function test_teacher_dashboard_hides_finance_kpis(): void
    {
        $this->seed(RoleSeeder::class);
        $auth = $this->createAuthenticatedUser(role: 'teacher');

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/dashboard/kpis')
            ->assertOk();

        $this->assertSame(0, $response->json('data.outstandingFees'));
        $this->assertSame(0, $response->json('data.totalRevenue'));
        $this->assertSame(0.0, (float) $response->json('data.payrollSummary.total_payroll'));
    }

    public function test_parent_cannot_list_academic_tests(): void
    {
        $this->seed(RoleSeeder::class);
        $auth = $this->createAuthenticatedUser(role: 'parent');

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->getJson('/api/v1/tests')
            ->assertForbidden();
    }

    public function test_production_seeder_creates_admin_without_demo_students(): void
    {
        config(['app.env' => 'production']);
        putenv('PROD_SCHOOL_CODE=PROD01');
        putenv('PROD_SCHOOL_NAME=Production School');
        putenv('PROD_ADMIN_EMAIL=prod-admin@example.test');
        putenv('PROD_ADMIN_PASSWORD=ProdPass123!');
        putenv('PROD_ADMIN_MUST_CHANGE_PASSWORD=false');

        $this->seed(ProductionSeeder::class);

        $this->assertDatabaseHas('schools', ['code' => 'PROD01']);
        $this->assertDatabaseHas('users', [
            'email' => 'prod-admin@example.test',
            'role' => 'admin',
        ]);
        $this->assertTrue(Hash::check('ProdPass123!', \App\Models\User::where('email', 'prod-admin@example.test')->first()->password));
        $this->assertSame(0, Student::query()->count());
    }

    public function test_soft_deleted_student_is_hidden_from_default_queries(): void
    {
        $auth = $this->createAuthenticatedUser();
        $student = Student::factory()->create(['school_id' => $auth['school']->id]);
        $student->delete();

        $this->assertNull(Student::query()->find($student->id));
        $this->assertNotNull(Student::withTrashed()->find($student->id));
    }
}
