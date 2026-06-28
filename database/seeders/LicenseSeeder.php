<?php

namespace Database\Seeders;

use App\Models\School;
use App\Services\LicenseService;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        $issuer = \App\Models\User::where('role', 'super_admin')->first()
            ?? \App\Models\User::where('email', 'admin@school.co.zw')->first();

        if (! config('license.enforcement', false)) {
            School::query()->update([
                'license_status' => 'active',
                'license_plan' => 'lifetime',
                'license_expires_at' => null,
            ]);
        }

        if (! $issuer) {
            return;
        }

        $licenseService = app(LicenseService::class);

        foreach (School::all() as $school) {
            if ($school->licenseKeys()->exists()) {
                continue;
            }

            $generated = $licenseService->generate($issuer, [
                'plan_type' => 'lifetime',
                'customer_name' => $school->name,
                'notes' => 'Demo lifetime license (seeded)',
            ]);

            $licenseService->activate($school, $generated['license_key']);
        }
    }
}
