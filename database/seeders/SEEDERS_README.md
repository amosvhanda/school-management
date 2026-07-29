# Database Seeders – Zimbabwe School Management System

## Overview

Seeders populate the database with **tenant-safe**, **relational** data. Every record has `school_id` (multi-school). Data uses realistic Zimbabwe names (`ZimbabweData` helper), **Faker** for addresses/optional fields, +263 phones, USD/ZWL, and payment methods (EcoCash, OneMoney, ZIPIT, POS, Bank, Cash).

**All seeders are idempotent**: re-running `php artisan db:seed` updates existing rows or skips creates where appropriate; no duplicate inserts or foreign-key errors.

## Production

Use `ProductionSeeder` only (see `docs/PRODUCTION.md`):

```bash
php artisan db:seed --class=ProductionSeeder --force
```

Full `db:seed` loads demo ERP data and must not run in production unless `SEED_DEMO_DATA=true` is intentionally set.

## Execution Order

```
Schools → Roles → Users → Teachers → Classes → Subjects → Students →
ParentStudent → Timetable → Attendance → Grades → Fee Structures →
Invoices → Payments → Transactions → Payroll → Settings →
ReportTemplates → Assignments → EnrollmentApplications → Announcements
```

## Commands

```bash
cd backend
php artisan migrate:fresh --seed
```

Or run migrations and seed separately:

```bash
php artisan migrate
php artisan db:seed
```

## Seeders Created or Updated

| Seeder | Status | Description |
|--------|--------|-------------|
| `SchoolSeeder` | unchanged | 3 schools (updateOrCreate by code) |
| `RoleSeeder` | unchanged | 4 roles (updateOrCreate by slug) |
| `UserSeeder` | unchanged | Admins, teacher/parent/student login users (updateOrCreate by email) |
| `TeacherSeeder` | **updated** | Idempotent by `employee_id`; deterministic email; `school_id`, `user_id` |
| `ClassSeeder` | unchanged | 7 classes per school; `teacher_id`, `school_id` |
| `SubjectSeeder` | **updated** | `class_id`, `teacher_id`; updateOrCreate by (school_id, name) |
| `StudentSeeder` | **updated** | `class_id`; deterministic `student_number`; idempotent |
| `ParentStudentSeeder` | unchanged | `school_id`; links parent user to students |
| `TimetableSeeder` | **updated** | `subject_id`; explicit lookup (class_id, day, start_time) for idempotency |
| `AttendanceSeeder` | **updated** | `teacher_id`; whereDate lookup for idempotency |
| `GradeSeeder` | **updated** | `subject_id`; updateOrCreate (student_id, subject_id, term, year) |
| `FeeStructureSeeder` | unchanged | `school_id`, `class_id`; firstOrCreate |
| `InvoiceSeeder` | **updated** | One invoice per (student, fee_structure); `fee_structure_id`; updateOrCreate |
| `PaymentSeeder` | **updated** | Idempotent by (invoice_id, reference); updates invoice balances |
| `TransactionSeeder` | **updated** | `payment_id`, `invoice_id`; updateOrCreate by payment_id |
| `PayrollSeeder` | unchanged | updateOrCreate (school_id, teacher_id, month, year) |
| `SettingSeeder` | unchanged | updateOrCreate (school_id, key) |
| `ReportTemplateSeeder` | **new** | Report templates per school; `created_by` → admin user |
| `AssignmentSeeder` | **new** | Assignments per class/teacher; `school_id`; avoids duplicates |
| `EnrollmentApplicationSeeder` | **new** | Applications per school; Faker for address etc.; `school_id` |
| `AnnouncementSeeder` | **updated** | Idempotent by (school_id, title); update or insert |

## Relationships Enforced

- **students** → `class_id`, `school_id`, `user_id`
- **subjects** → `school_id`, `class_id`, `teacher_id`
- **timetable** → `class_id`, `teacher_id`, `subject_id`, `school_id`
- **attendance** → `student_id`, `class_id`, `teacher_id`, `school_id`, `marked_by`
- **grades** → `student_id`, `class_id`, `subject_id`, `teacher_id`, `school_id`
- **invoices** → `student_id`, `fee_structure_id`, `school_id`
- **payments** → `invoice_id`, `student_id`, `school_id`, `created_by`
- **transactions** → `payment_id`, `invoice_id`, `student_id`, `school_id`, `created_by`
- **parent_student** → `parent_id`, `student_id`, `school_id`
- **assignments** → `class_id`, `teacher_id`, `school_id`
- **enrollment_applications** → `school_id`, `reviewed_by`
- **report_templates** → `school_id`, `created_by`

## Sample Record Counts (after `migrate:fresh --seed`)

| Table | Approximate count |
|-------|-------------------|
| schools | 3 |
| roles | 4 |
| users | 7 |
| students | 90 (5 fixed + 25 per school × 3) |
| teachers | 15 (5 per school) |
| classes | 21 (7 per school) |
| subjects | 18 (6 per school) |
| timetable | 630 (21 classes × 5 days × 6 slots) |
| attendance | 567 (7 days × active students) |
| grades | 324 (4 subjects × active students) |
| fee_structures | 126 (6 categories × 7 classes × 3 schools) |
| invoices | 486+ (one per student per fee structure) |
| payments | 20 |
| transactions | 20 |
| payroll | 15 |
| report_templates | 12 (4 per school) |
| assignments | 12 |
| enrollment_applications | 15 (5 per school) |
| parent_student | 5 |
| settings | 12 |
| announcements | 12 |

## Verification

```bash
cd backend
php artisan migrate:fresh --seed
php artisan db:seed   # idempotent; no errors, no duplicates
php artisan tinker --execute="
  foreach (['schools','roles','users','students','teachers','classes','subjects','timetable','attendance','grades','fee_structures','invoices','payments','transactions','payroll','report_templates','assignments','enrollment_applications','parent_student','settings','announcements'] as \$t) {
    echo \$t . ': ' . \DB::table(\$t)->count() . PHP_EOL;
  }
"
```

## Login Users

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@school.co.zw` | `admin123` |
| Teacher | `teacher@school.co.zw` | `teacher123` |
| Parent | `parent@school.co.zw` | `parent123` |
| Student | `student@school.co.zw` | `student123` |

## Constraints

- No hardcoded IDs; all relationships use model lookups.
- Seeders run cleanly with `php artisan migrate:fresh --seed`.
- Re-running `php artisan db:seed` is safe (idempotent).
- No tables are deleted; existing data is preserved where possible via updateOrCreate / firstOrCreate.
