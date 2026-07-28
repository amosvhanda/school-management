<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case SchoolAdmin = 'school_admin';
    case Teacher = 'teacher';
    case Parent = 'parent';
    case Student = 'student';
    case Finance = 'finance';
    case Accounts = 'accounts';
    case ExaminationOfficer = 'examination_officer';
    case Receptionist = 'receptionist';
    case Librarian = 'librarian';
    case Nurse = 'nurse';
    case TransportManager = 'transport_manager';
    case HostelManager = 'hostel_manager';

    /**
     * Role label exposed to API clients (normalizes school_admin → admin).
     */
    public function apiValue(): string
    {
        return match ($this) {
            self::SchoolAdmin => self::Admin->value,
            default => $this->value,
        };
    }

    public static function tryFromMixed(?string $role): ?self
    {
        if ($role === null) {
            return null;
        }

        return self::tryFrom($role) ?? match ($role) {
            'admin' => self::Admin,
            default => null,
        };
    }

    public function hasSchoolContext(): bool
    {
        return $this !== self::SuperAdmin;
    }

    public function canManageStudents(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Teacher, self::Nurse], true);
    }

    public function canManageTeachers(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin], true);
    }

    public function canViewAuditLogs(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Finance, self::Accounts], true);
    }

    public function canManageExaminations(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::ExaminationOfficer], true);
    }

    public function canEnterExamResults(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Teacher, self::ExaminationOfficer], true);
    }

    public function canManageFinance(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Finance, self::Accounts], true);
    }

    /**
     * Capability flags exposed to API clients (single source of truth for UI authorization).
     *
     * @return array<string, bool>
     */
    public function canManageLibrary(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Librarian], true);
    }

    public function canManageTransport(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::TransportManager], true);
    }

    public function canManageInventory(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin], true);
    }

    public function canManageReception(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Receptionist], true);
    }

    public function canManageHealth(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::Nurse], true);
    }

    public function canManageHostel(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin, self::SchoolAdmin, self::HostelManager], true);
    }

    public function capabilities(): array
    {
        return [
            'isSuperAdmin' => $this === self::SuperAdmin,
            'isParent' => $this === self::Parent,
            'isStaff' => $this !== self::Parent && $this !== self::Student,
            'canManageStudents' => $this->canManageStudents(),
            'canManageTeachers' => $this->canManageTeachers(),
            'canManageFinance' => $this->canManageFinance(),
            'canViewAuditLogs' => $this->canViewAuditLogs(),
            'canManageExaminations' => $this->canManageExaminations(),
            'canEnterExamResults' => $this->canEnterExamResults(),
            'canManageLibrary' => $this->canManageLibrary(),
            'canManageTransport' => $this->canManageTransport(),
            'canManageInventory' => $this->canManageInventory(),
            'canManageReception' => $this->canManageReception(),
            'canManageHealth' => $this->canManageHealth(),
            'canManageHostel' => $this->canManageHostel(),
        ];
    }
}
