<?php

namespace Database\Seeders;

use App\Models\CustomField;
use App\Models\LoginHistory;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\TerminologyMapping;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoSchoolContext;
use Illuminate\Database\Seeder;

class CoreReferenceSeeder extends Seeder
{
    use ResolvesDemoSchoolContext;

    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            FeeCategoriesSeeder::class,
        ]);

        $primarySchool = School::query()->orderBy('id')->first();

        if ($primarySchool) {
            School::query()
                ->whereKeyNot($primarySchool->id)
                ->update(['parent_school_id' => $primarySchool->id, 'branch_type' => 'branch']);
        }

        foreach ($this->demoSchools() as $school) {
            $this->seedSchoolSettings($school);
            $this->seedCustomFields($school);
            $this->seedTerminology($school);
            $this->seedLoginHistory($school);
        }
    }

    private function seedSchoolSettings(School $school): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'timezone', 'value' => 'Africa/Harare', 'type' => 'string', 'is_public' => true],
            ['group' => 'general', 'key' => 'locale', 'value' => 'en', 'type' => 'string', 'is_public' => true],
            ['group' => 'academic', 'key' => 'grading_system', 'value' => 'percentage', 'type' => 'string', 'is_public' => false],
            ['group' => 'finance', 'key' => 'allow_instalments', 'value' => 'true', 'type' => 'boolean', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            SchoolSetting::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'group' => $setting['group'],
                    'key' => $setting['key'],
                ],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                ]
            );
        }
    }

    private function seedCustomFields(School $school): void
    {
        CustomField::updateOrCreate(
            [
                'school_id' => $school->id,
                'entity_type' => Student::class,
                'slug' => 'previous-school',
            ],
            [
                'name' => 'Previous School',
                'field_type' => 'text',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    private function seedTerminology(School $school): void
    {
        $mappings = [
            ['system_key' => 'student', 'custom_label' => 'Learner'],
            ['system_key' => 'teacher', 'custom_label' => 'Educator'],
            ['system_key' => 'class', 'custom_label' => 'Form'],
        ];

        foreach ($mappings as $mapping) {
            TerminologyMapping::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'system_key' => $mapping['system_key'],
                    'locale' => 'en',
                ],
                ['custom_label' => $mapping['custom_label']]
            );
        }
    }

    private function seedLoginHistory(School $school): void
    {
        $users = User::query()->where('school_id', $school->id)->limit(3)->get();

        foreach ($users as $user) {
            LoginHistory::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'event' => 'login_success',
                    'email' => $user->email,
                ],
                [
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Demo Seeder',
                    'device_type' => 'desktop',
                    'platform' => 'macOS',
                    'location' => 'Harare, Zimbabwe',
                    'created_at' => now()->subDays(1),
                ]
            );
        }
    }
}
