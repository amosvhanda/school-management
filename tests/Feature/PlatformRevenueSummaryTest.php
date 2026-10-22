<?php

namespace Tests\Feature;

use App\Enums\LicenseKeyStatus;
use App\Enums\LicensePlanType;
use App\Enums\UserRole;
use App\Models\LicenseKey;
use App\Models\User;
use Tests\TestCase;

class PlatformRevenueSummaryTest extends TestCase
{
    public function test_platform_summary_includes_recognized_license_revenue(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'school_id' => null,
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        LicenseKey::create([
            'key_prefix' => 'SKERP-REV1',
            'key_hash' => hash('sha256', 'test-key-1'),
            'plan_type' => LicensePlanType::Annual,
            'duration_months' => 12,
            'amount' => 449,
            'currency' => 'USD',
            'status' => LicenseKeyStatus::Active,
            'activated_at' => now(),
            'paid_at' => now(),
            'created_by' => $admin->id,
        ]);

        LicenseKey::create([
            'key_prefix' => 'SKERP-REV2',
            'key_hash' => hash('sha256', 'test-key-2'),
            'plan_type' => LicensePlanType::Monthly,
            'duration_months' => 1,
            'amount' => 49,
            'currency' => 'USD',
            'status' => LicenseKeyStatus::Unused,
            'created_by' => $admin->id,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/admin/licenses')
            ->assertOk();

        $revenue = $response->json('data.summary.revenue');
        $this->assertSame('USD', $revenue['currency']);
        $this->assertEquals(449.0, (float) $revenue['mtd']);
        $this->assertEquals(449.0, (float) $revenue['ytd']);
        $this->assertEquals(449.0, (float) $revenue['recognized_total']);
        $this->assertEquals(449.0, (float) $revenue['by_plan']['annual']);
    }
}
