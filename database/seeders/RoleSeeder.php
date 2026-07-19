<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissionIds = collect(config('permissions', []))
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
                'description' => 'Teaching staff — attendance, marks, and class tools. Add library/transport etc. via extra permissions on the user or role.',
                // No academics.manage (14): that unlocks school-wide setup / canManageTeachers.
                'permission_ids' => [5, 10, 12, 15, 19, 22],
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
