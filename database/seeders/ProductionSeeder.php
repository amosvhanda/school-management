<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Minimal production bootstrap: roles + one school + school admin (+ optional platform admin).
 * Does not seed demo students, invoices, or sample ERP modules.
 *
 * Env:
 *   PROD_SCHOOL_NAME, PROD_SCHOOL_CODE
 *   PROD_ADMIN_NAME, PROD_ADMIN_EMAIL, PROD_ADMIN_PASSWORD
 *   PROD_SUPER_ADMIN_EMAIL, PROD_SUPER_ADMIN_PASSWORD (optional)
 */
class ProductionSeeder extends Seeder
{
    private function envOr(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = env($key, $default);
        }

        return $value === null || $value === '' ? $default : (string) $value;
    }

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CoreReferenceSeeder::class,
        ]);

        $schoolName = (string) $this->envOr('PROD_SCHOOL_NAME', 'My School');
        $schoolCode = strtoupper((string) $this->envOr('PROD_SCHOOL_CODE', 'SCHOOL01'));

        $school = School::updateOrCreate(
            ['code' => $schoolCode],
            [
                'name' => $schoolName,
                'address' => $this->envOr('PROD_SCHOOL_ADDRESS'),
                'phone' => $this->envOr('PROD_SCHOOL_PHONE'),
                'email' => $this->envOr('PROD_SCHOOL_EMAIL'),
                'currency_default' => $this->envOr('PROD_SCHOOL_CURRENCY', 'USD'),
                'academic_year' => (string) now()->year,
                'current_term' => 'Term 1',
                'settings' => null,
            ]
        );

        $adminEmail = (string) $this->envOr('PROD_ADMIN_EMAIL', 'admin@'.$schoolCode.'.school.local');
        $adminPassword = (string) $this->envOr('PROD_ADMIN_PASSWORD', Str::password(16));
        $adminName = (string) $this->envOr('PROD_ADMIN_NAME', 'School Administrator');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'first_name' => explode(' ', $adminName)[0] ?? 'Admin',
                'last_name' => explode(' ', $adminName)[1] ?? 'User',
                'school_id' => $school->id,
                'status' => 'active',
                'must_change_password' => filter_var($this->envOr('PROD_ADMIN_MUST_CHANGE_PASSWORD', 'true'), FILTER_VALIDATE_BOOL),
            ]
        );

        if ($superEmail = $this->envOr('PROD_SUPER_ADMIN_EMAIL')) {
            $superPassword = (string) $this->envOr('PROD_SUPER_ADMIN_PASSWORD', Str::password(20));
            User::updateOrCreate(
                ['email' => $superEmail],
                [
                    'name' => $this->envOr('PROD_SUPER_ADMIN_NAME', 'Platform Administrator'),
                    'password' => Hash::make($superPassword),
                    'role' => 'super_admin',
                    'first_name' => 'Platform',
                    'last_name' => 'Admin',
                    'school_id' => null,
                    'status' => 'active',
                    'must_change_password' => filter_var($this->envOr('PROD_SUPER_ADMIN_MUST_CHANGE_PASSWORD', 'true'), FILTER_VALIDATE_BOOL),
                ]
            );
        }

        if ($this->command) {
            $this->command->info('Production bootstrap complete.');
            $this->command->warn("School admin: {$adminEmail}");
            if (! $this->envOr('PROD_ADMIN_PASSWORD')) {
                $this->command->warn("Generated admin password: {$adminPassword}");
            }
            if ($superEmail && ! $this->envOr('PROD_SUPER_ADMIN_PASSWORD')) {
                $this->command->warn('Generated super-admin password was printed above only if generated — check env.');
            }
        }
    }
}
