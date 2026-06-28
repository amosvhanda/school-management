<?php

namespace Database\Seeders;

use App\Models\Payroll;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        $month = (int) now()->format('n');
        $year = (int) now()->format('Y');

        foreach (Teacher::whereNotNull('school_id')->get() as $teacher) {
            $base = rand(800, 1200);
            $deductions = rand(50, 120);
            $net = $base - $deductions;
            $statuses = ['pending', 'processed', 'paid'];
            $status = $statuses[array_rand($statuses)];
            Payroll::updateOrCreate(
                [
                    'school_id' => $teacher->school_id,
                    'teacher_id' => $teacher->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'base_salary' => $base,
                    'gross_salary' => $base,
                    'deductions_total' => $deductions,
                    'net_salary' => $net,
                    'currency' => 'USD',
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? now()->format('Y-m-d') : null,
                    'processed_by' => in_array($status, ['processed', 'paid'], true) ? $admin->id : null,
                    'notes' => null,
                ]
            );
        }
    }
}
