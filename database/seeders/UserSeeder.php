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

    private static ?string $teacherPassword = null;

    private static ?string $parentPassword = null;

    private static ?string $studentPassword = null;

    private static ?string $financePassword = null;

    private static ?string $accountsPassword = null;

    private static ?string $examPassword = null;

    private static ?string $superAdminPassword = null;

    public function run(): void
    {
        static::$adminPassword ??= Hash::make('admin123');
        static::$teacherPassword ??= Hash::make('teacher123');
        static::$parentPassword ??= Hash::make('parent123');
        static::$studentPassword ??= Hash::make('student123');
        static::$financePassword ??= Hash::make('finance123');
        static::$accountsPassword ??= Hash::make('accounts123');
        static::$examPassword ??= Hash::make('exam123');
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

        // Admin per school (school-specific emails)
        foreach ($schools as $school) {
            User::updateOrCreate(
                ['email' => 'admin@' . strtolower($school->code) . '.school.co.zw'],
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

        // Fixed teacher, parent, student (for login)
        User::updateOrCreate(
            ['email' => 'teacher@school.co.zw'],
            [
                'name' => 'Tinashe Moyo',
                'password' => static::$teacherPassword,
                'role' => 'teacher',
                'first_name' => 'Tinashe',
                'last_name' => 'Moyo',
                'phone' => ZimbabweData::phone(),
                'school_id' => $primary->id,
            ]
        );
        User::updateOrCreate(
            ['email' => 'parent@school.co.zw'],
            [
                'name' => 'Tatenda Moyo',
                'password' => static::$parentPassword,
                'role' => 'parent',
                'first_name' => 'Tatenda',
                'last_name' => 'Moyo',
                'phone' => ZimbabweData::phone(),
                'school_id' => $primary->id,
            ]
        );
        User::updateOrCreate(
            ['email' => 'student@school.co.zw'],
            [
                'name' => 'Nyasha Chiremba',
                'password' => static::$studentPassword,
                'role' => 'student',
                'first_name' => 'Nyasha',
                'last_name' => 'Chiremba',
                'phone' => ZimbabweData::phone(),
                'school_id' => $primary->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'finance@school.co.zw'],
            [
                'name' => 'Rudo Ncube',
                'password' => static::$financePassword,
                'role' => 'finance',
                'first_name' => 'Rudo',
                'last_name' => 'Ncube',
                'phone' => ZimbabweData::phone(),
                'school_id' => $primary->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'accounts@school.co.zw'],
            [
                'name' => 'Farai Dube',
                'password' => static::$accountsPassword,
                'role' => 'accounts',
                'first_name' => 'Farai',
                'last_name' => 'Dube',
                'phone' => ZimbabweData::phone(),
                'school_id' => $primary->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'exam@school.co.zw'],
            [
                'name' => 'Chipo Mutasa',
                'password' => static::$examPassword,
                'role' => 'examination_officer',
                'first_name' => 'Chipo',
                'last_name' => 'Mutasa',
                'phone' => ZimbabweData::phone(),
                'school_id' => $primary->id,
            ]
        );
    }
}
