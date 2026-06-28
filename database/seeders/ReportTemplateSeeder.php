<?php

namespace Database\Seeders;

use App\Models\ReportTemplate;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        $templates = [
            ['name' => 'Academic Performance', 'category' => 'academic', 'frequency' => 'termly', 'description' => 'Student grades and subject performance by class'],
            ['name' => 'Attendance Summary', 'category' => 'attendance', 'frequency' => 'monthly', 'description' => 'Daily attendance rates per class and student'],
            ['name' => 'Financial Summary', 'category' => 'financial', 'frequency' => 'monthly', 'description' => 'Fee collection, outstanding balances, and revenue'],
            ['name' => 'Payroll Register', 'category' => 'payroll', 'frequency' => 'monthly', 'description' => 'Teacher salaries, deductions, and payment status'],
        ];

        foreach (School::all() as $school) {
            foreach ($templates as $t) {
                ReportTemplate::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $t['name'],
                    ],
                    [
                        'description' => $t['description'],
                        'category' => $t['category'],
                        'frequency' => $t['frequency'],
                        'recipients' => ['admin', 'accounts'],
                        'parameters' => [],
                        'created_by' => $admin->id,
                    ]
                );
            }
        }
    }
}
