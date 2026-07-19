<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SchoolProvisioningService
{
    public function __construct(
        private SchoolConfigurationService $configService,
        private SchoolSettingsService $settingsService,
        private TerminologyService $terminologyService,
        private LicenseService $licenseService,
    ) {}

    /**
     * Create a school profile, seed defaults, create the school admin, and optionally license it.
     *
     * @param  array{
     *   school_name: string,
     *   school_code: string,
     *   admin_name: string,
     *   admin_email: string,
     *   admin_password: string,
     *   address?: string|null,
     *   phone?: string|null,
     *   email?: string|null,
     *   currency?: string|null,
     *   contact_person?: string|null,
     *   contact_phone?: string|null,
     *   contact_email?: string|null,
     *   academic_year?: string|null,
     *   current_term?: string|null,
     *   grade_levels?: array|null,
     *   grading_scale?: array|null,
     *   license_key?: string|null,
     *   generate_and_activate_license?: bool,
     *   plan_type?: string|null,
     *   duration_months?: int|null,
     *   customer_name?: string|null,
     *   customer_email?: string|null,
     *   notes?: string|null,
     * }  $data
     * @return array{
     *   school: School,
     *   admin: User,
     *   license_key: string|null,
     *   license_status: string|null,
     * }
     */
    public function provision(array $data, ?User $issuer = null): array
    {
        return DB::transaction(function () use ($data, $issuer) {
            $currency = $data['currency'] ?? 'USD';

            $school = School::create([
                'name' => $data['school_name'],
                'code' => $data['school_code'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'contact_person' => $data['contact_person'] ?? ($data['admin_name'] ?? null),
                'contact_phone' => $data['contact_phone'] ?? ($data['phone'] ?? null),
                'contact_email' => $data['contact_email'] ?? ($data['admin_email'] ?? null),
                'currency' => $currency,
                'currency_default' => $currency,
                'academic_year' => $data['academic_year'] ?? (string) now()->year,
                'current_term' => $data['current_term'] ?? 'Term 1',
                'status' => 'active',
                'license_status' => config('license.enforcement') ? 'none' : 'active',
                'license_plan' => config('license.enforcement') ? null : 'lifetime',
                'license_expires_at' => null,
            ]);

            $plainLicenseKey = null;

            if (! empty($data['generate_and_activate_license'])) {
                if (! $issuer) {
                    throw ValidationException::withMessages([
                        'generate_and_activate_license' => ['An issuer is required to generate a license.'],
                    ]);
                }

                $generated = $this->licenseService->generate($issuer, [
                    'plan_type' => $data['plan_type'] ?? 'annual',
                    'duration_months' => $data['duration_months'] ?? null,
                    'customer_name' => $data['customer_name'] ?? $data['school_name'],
                    'customer_email' => $data['customer_email'] ?? $data['admin_email'],
                    'notes' => $data['notes'] ?? 'Generated during school provisioning',
                ]);
                $plainLicenseKey = $generated['license_key'];
                $this->licenseService->activate($school, $plainLicenseKey);
                $school->refresh();
            } elseif (! empty($data['license_key'])) {
                $plainLicenseKey = $data['license_key'];
                $this->licenseService->activate($school, $plainLicenseKey);
                $school->refresh();
            } elseif (! config('license.enforcement')) {
                $school->update([
                    'license_status' => 'active',
                    'license_plan' => 'lifetime',
                ]);
            }

            $nameParts = preg_split('/\s+/', trim($data['admin_name'])) ?: [];
            $firstName = $nameParts[0] ?? $data['admin_name'];
            $lastName = implode(' ', array_slice($nameParts, 1));

            $admin = User::create([
                'name' => $data['admin_name'],
                'first_name' => $firstName,
                'last_name' => $lastName !== '' ? $lastName : '',
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role' => UserRole::Admin,
                'school_id' => $school->id,
            ]);

            $this->configService->initializeDefaultGradeLevels($school, $data['grade_levels'] ?? null);
            $this->configService->initializeDefaultGradingScale($school, $data['grading_scale'] ?? null);
            $this->settingsService->seedDefaults($school);
            $this->terminologyService->seedDefaults($school);

            return [
                'school' => $school->fresh(),
                'admin' => $admin,
                'license_key' => $plainLicenseKey,
                'license_status' => $school->fresh()->license_status,
            ];
        });
    }
}
