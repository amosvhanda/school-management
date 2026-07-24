<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = collect(config('permissions', []));
        $allPermissionIds = $catalog->pluck('id')->filter()->values()->all();

        $teacherSlugs = config('teacher_permissions.slugs', [
            'reports.view', 'dashboard.view', 'students.manage',
            'attendance.manage', 'communications.manage', 'exams.enter_results',
        ]);
        $teacherPermissionIds = $catalog
            ->whereIn('slug', $teacherSlugs)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access. Grant module permissions to other roles instead of creating new login types.',
                'permission_ids' => $allPermissionIds,
            ],
            [
                'name' => 'Teacher',
                'slug' => 'teacher',
                'description' => 'Teaching staff — assigned classes only. See config/teacher_permissions.php and docs/teacher-permissions.md. Add library/transport etc. via user overrides.',
                // Never includes academics.manage (canManageTeachers) or exams.manage.
                'permission_ids' => $teacherPermissionIds,
            ],
            [
                'name' => 'Parent',
                'slug' => 'parent',
                'description' => 'Parent or guardian portal access',
                'permission_ids' => [10],
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Student portal access',
                'permission_ids' => [10],
            ],
            [
                'name' => 'Finance Department',
                'slug' => 'finance',
                'description' => 'Finance department staff',
                'permission_ids' => [5, 6, 8, 10, 11, 17],
            ],
            [
                'name' => 'Accounts Department',
                'slug' => 'accounts',
                'description' => 'Accounts and reconciliation staff',
                'permission_ids' => [5, 6, 8, 10, 11, 17],
            ],
            [
                'name' => 'Examination Officer',
                'slug' => 'examination_officer',
                'description' => 'Exam setup, marks approval, and publication',
                'permission_ids' => [5, 6, 10, 15, 16],
            ],
        ];
        foreach ($roles as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
