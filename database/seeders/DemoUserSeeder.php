<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Database\Seeders\Helpers\ZimbabweData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    private static ?string $teacherPassword = null;

    private static ?string $parentPassword = null;

    private static ?string $studentPassword = null;

    private static ?string $financePassword = null;

    private static ?string $accountsPassword = null;

    private static ?string $examPassword = null;

    public function run(): void
    {
        static::$teacherPassword ??= Hash::make('teacher123');
        static::$parentPassword ??= Hash::make('parent123');
        static::$studentPassword ??= Hash::make('student123');
        static::$financePassword ??= Hash::make('finance123');
        static::$accountsPassword ??= Hash::make('accounts123');
        static::$examPassword ??= Hash::make('exam123');

        $schools = School::all();
        if ($schools->isEmpty()) {
            return;
        }

        $primary = $schools->first();

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
                'status' => 'active',
                // Rely on RoleSeeder teacher permissions — do not override with empty/stale ids.
                'permission_ids' => null,
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
