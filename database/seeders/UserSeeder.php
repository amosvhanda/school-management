<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private static ?string $adminPassword = null;

    private static ?string $superAdminPassword = null;

    public function run(): void
    {
        static::$adminPassword ??= Hash::make('admin123');
        static::$superAdminPassword ??= Hash::make('super123');

        // Platform admin — no school required; always seed even when no schools exist yet.
        User::updateOrCreate(
            ['email' => 'super@school.co.zw'],
            [
                'name' => 'Platform Administrator',
                'password' => static::$superAdminPassword,
                'role' => 'super_admin',
                'first_name' => 'Platform',
                'last_name' => 'Admin',
                'phone' => ZimbabweData::phone(),
                'school_id' => null,
                'status' => 'active',
            ]
        );

        $schools = School::all();
        if ($schools->isEmpty()) {
            return;
        }

        $primary = $schools->first();

        // Primary admin (admin@school.co.zw) for first school – matches INSTALLATION_GUIDE
        User::updateOrCreate(
            ['email' => 'admin@school.co.zw'],
            [
                'name' => 'Administrator',
                'password' => static::$adminPassword,
                'role' => 'admin',
                'first_name' => 'Administrator',
                'last_name' => 'System',
                'school_id' => $primary->id,
            ]
        );

        // Demo role logins are created here so TeacherSeeder can link teacher@school.co.zw.
        $this->call(DemoUserSeeder::class);

        // Admin per school (school-specific emails)
        foreach ($schools as $school) {
            User::updateOrCreate(
                ['email' => 'admin@'.strtolower($school->code).'.school.co.zw'],
                [
                    'name' => 'Administrator',
                    'password' => static::$adminPassword,
                    'role' => 'admin',
                    'first_name' => 'Administrator',
                    'last_name' => 'System',
                    'school_id' => $school->id,
                ]
            );
        }

    }
}
