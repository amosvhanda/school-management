<?php

/**
 * Canonical Teacher role permission matrix.
 *
 * Used by RoleSeeder, PermissionService defaults, and feature tests.
 * Principle: grant by slug + restrict by TeacherAssignment (own classes/subjects).
 *
 * Never grant teachers (by default): academics.manage, exams.manage,
 * enrollment.manage, hr.manage, finance.*, audit.view, users.*, roles.manage,
 * settings.manage, library/transport/inventory/reception.manage.
 */
return [
    /**
     * Permission slugs granted to the seeded Teacher role.
     *
     * @var list<string>
     */
    'slugs' => [
        'reports.view',
        'reports.generate', // own-class CSV / printable exports
        'dashboard.view',
        'students.manage',
        'attendance.manage',
        'communications.manage',
        'exams.enter_results',
    ],

    /**
     * Capabilities that must resolve true for a seeded teacher.
     *
     * @var list<string>
     */
    'capabilities_granted' => [
        'isStaff',
        'canManageStudents',
        'canEnterExamResults',
    ],

    /**
     * Capabilities that must resolve false for a seeded teacher.
     *
     * @var list<string>
     */
    'capabilities_denied' => [
        'isSuperAdmin',
        'isParent',
        'canManageTeachers',
        'canManageFinance',
        'canViewAuditLogs',
        'canManageExaminations',
        'canManageLibrary',
        'canManageTransport',
        'canManageInventory',
        'canManageReception',
    ],

    /**
     * Permission slugs that must never appear on the default Teacher role.
     *
     * @var list<string>
     */
    'slugs_denied' => [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'roles.manage',
        'settings.manage',
        'audit.view',
        'enrollment.manage',
        'academics.manage',
        'exams.manage',
        'finance.manage',
        'transactions.view',
        'hr.manage',
        'operations.manage',
        'library.manage',
        'transport.manage',
        'inventory.manage',
        'reception.manage',
        'compliance.manage',
    ],
];
