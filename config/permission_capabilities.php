<?php

/**
 * Maps permission slugs (from config/permissions.php) to UI/API capability flags.
 */
return [
    'users.view' => ['isStaff'],
    'users.create' => ['isStaff', 'canManageTeachers'],
    'users.edit' => ['isStaff', 'canManageTeachers'],
    'users.delete' => ['isStaff', 'canManageTeachers'],
    'reports.view' => ['isStaff'],
    'reports.generate' => ['isStaff'],
    'roles.manage' => ['isStaff', 'canManageTeachers'],
    'transactions.view' => ['isStaff', 'canManageFinance'],
    'settings.manage' => ['isStaff', 'canManageTeachers'],
    'dashboard.view' => ['isStaff'],
    'audit.view' => ['isStaff', 'canViewAuditLogs'],
    'students.manage' => ['isStaff', 'canManageStudents'],
    'enrollment.manage' => ['isStaff', 'canManageStudents'],
    'academics.manage' => ['isStaff', 'canManageTeachers'],
    'attendance.manage' => ['isStaff', 'canManageStudents'],
    'exams.manage' => ['isStaff', 'canManageExaminations'],
    'exams.enter_results' => ['isStaff', 'canEnterExamResults'],
    'finance.manage' => ['isStaff', 'canManageFinance'],
    'hr.manage' => ['isStaff', 'canManageTeachers'],
    'communications.manage' => ['isStaff'],
    'operations.manage' => [
        'isStaff',
        'canManageLibrary',
        'canManageTransport',
        'canManageInventory',
        'canManageReception',
    ],
    'library.manage' => ['isStaff', 'canManageLibrary'],
    'transport.manage' => ['isStaff', 'canManageTransport'],
    'inventory.manage' => ['isStaff', 'canManageInventory'],
    'reception.manage' => ['isStaff', 'canManageReception'],
    'compliance.manage' => ['isStaff', 'canManageTeachers'],
];
