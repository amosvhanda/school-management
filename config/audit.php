<?php

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\DisciplinaryRecord;
use App\Models\EnrollmentApplication;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeeStructure;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\LeaveRequest;
use App\Models\LibraryBook;
use App\Models\LibraryLoan;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\Transaction;
use App\Models\User;

return [
    'enabled' => env('AUDIT_ENABLED', true),

    'sensitive_attributes' => [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'current_password',
        'token',
        'integration_key',
        'hash',
        'credentials',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP activity logging
    |--------------------------------------------------------------------------
    | LogApiActivity middleware records successful mutating API requests.
    | Paths use Laravel Str::is patterns (leading slash, no domain).
    */
    'http_exclude_paths' => [
        'api/v1/auth/login',
        'api/v1/auth/logout',
        'api/v1/auth/forgot-password',
        'api/v1/auth/reset-password',
        'api/v1/auth/two-factor-challenge',
        'api/*/webhooks/*',
        'api/v1/audit-logs',
        'api/v1/audit-logs/*',
        'up',
        'sanctum/csrf-cookie',
    ],

    'http_modules' => [
        'students' => 'student',
        'teachers' => 'teacher',
        'guardians' => 'guardian',
        'classes' => 'academic',
        'subjects' => 'academic',
        'terms' => 'academic',
        'streams' => 'academic',
        'rooms' => 'academic',
        'grade-levels' => 'academic',
        'exams' => 'examination',
        'tests' => 'examination',
        'assignments' => 'academic',
        'grades' => 'examination',
        'attendance' => 'attendance',
        'staff-attendance' => 'hr',
        'invoices' => 'finance',
        'payments' => 'finance',
        'transactions' => 'finance',
        'fee-structures' => 'finance',
        'fee-categories' => 'finance',
        'fee-groups' => 'finance',
        'fee-discounts' => 'finance',
        'payroll' => 'hr',
        'leave-requests' => 'hr',
        'leave-types' => 'hr',
        'users' => 'administration',
        'roles' => 'administration',
        'announcements' => 'communication',
        'communications' => 'communication',
        'hostels' => 'operations',
        'library' => 'operations',
        'transport' => 'operations',
        'inventory' => 'operations',
        'assets' => 'operations',
        'workflows' => 'workflow',
        'enrollment-applications' => 'enrollment',
        'disciplinary-records' => 'discipline',
        'reports' => 'reports',
        'enterprise' => 'enterprise',
        'platform' => 'platform',
        'teacher-portal' => 'teacher',
        'student-portal' => 'student',
        'parent' => 'guardian',
        'settings' => 'administration',
        'school' => 'administration',
    ],

    'model_modules' => [
        Student::class => 'student',
        Teacher::class => 'teacher',
        User::class => 'administration',
        Payment::class => 'finance',
        Invoice::class => 'finance',
        Transaction::class => 'finance',
        Payroll::class => 'hr',
        Exam::class => 'examination',
        ExamResult::class => 'examination',
        DisciplinaryRecord::class => 'discipline',
        EnrollmentApplication::class => 'enrollment',
        Attendance::class => 'attendance',
        Guardian::class => 'guardian',
        LeaveRequest::class => 'hr',
        FeeStructure::class => 'finance',
        ClassModel::class => 'academic',
        Subject::class => 'academic',
        Term::class => 'academic',
        GradeLevel::class => 'academic',
        Assignment::class => 'academic',
        Announcement::class => 'communication',
        JournalEntry::class => 'finance',
        Hostel::class => 'operations',
        HostelAllocation::class => 'operations',
        LibraryBook::class => 'operations',
        LibraryLoan::class => 'operations',
    ],
];
