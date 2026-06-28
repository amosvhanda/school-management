<?php

return [
    'enabled' => env('AUDIT_ENABLED', true),

    'sensitive_attributes' => [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],

    'model_modules' => [
        \App\Models\Student::class => 'student',
        \App\Models\User::class => 'administration',
        \App\Models\Payment::class => 'finance',
        \App\Models\Invoice::class => 'finance',
        \App\Models\Transaction::class => 'finance',
        \App\Models\Exam::class => 'examination',
        \App\Models\ExamResult::class => 'examination',
        \App\Models\DisciplinaryRecord::class => 'discipline',
        \App\Models\EnrollmentApplication::class => 'enrollment',
        \App\Models\Attendance::class => 'attendance',
        \App\Models\Guardian::class => 'guardian',
        \App\Models\LeaveRequest::class => 'hr',
        \App\Models\FeeStructure::class => 'finance',
    ],
];
