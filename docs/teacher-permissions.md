# Teacher permissions

Single source of truth in code: `config/teacher_permissions.php` (seeded by `RoleSeeder`, mirrored in `PermissionService` defaults).

## Principles

1. **Role grants coarse access** via permission slugs → UI capabilities.
2. **Ownership scopes data** to `TeacherAssignment` (and class teacher of record).
3. **Opt-in extras** (library, transport, etc.) go on the user or role override — not the base Teacher seed.

## Default Teacher slugs

| Slug | Enables |
|------|---------|
| `dashboard.view` | Staff dashboard |
| `reports.view` | View own-scope reports |
| `reports.generate` | Export CSV / printable HTML for own classes |
| `students.manage` | Students in assigned classes (`canManageStudents`) |
| `attendance.manage` | Mark / submit attendance for assigned classes |
| `communications.manage` | Messages & announcements |
| `exams.enter_results` | Enter marks / CA / narrative comments (`canEnterExamResults`) |

## Capabilities

**Granted:** `isStaff`, `canManageStudents`, `canEnterExamResults`  
**Denied:** `canManageTeachers`, `canManageExaminations`, `canManageFinance`, `canViewAuditLogs`, ops modules, `isSuperAdmin`, `isParent`

## Never grant by default

`academics.manage`, `exams.manage`, `enrollment.manage`, `hr.manage`, finance/audit/users/roles/settings, library/transport/inventory/reception/compliance.

## 24 portal areas (allow + scope)

| Area | Gate | Scope |
|------|------|--------|
| Dashboard | `isStaff` | Own assignments |
| Profile | Authenticated | Self |
| My Classes | `canManageStudents` | Assigned classes |
| Attendance | `attendance.manage` | Assigned classes; submit/lock own registers |
| Timetable | `isStaff` | Own timetable / change requests |
| Lesson plans | `isStaff` | Own plans |
| Syllabus | `isStaff` | Assigned subjects |
| Homework | `isStaff` | Own assignments |
| LMS | `isStaff` | Own lessons |
| Exams | `exams.enter_results` only | Results entry — not create/publish |
| Marks | `exams.enter_results` | Assigned subjects |
| Report cards | `exams.enter_results` | Own comments |
| Assessment | `exams.enter_results` | Assigned classes |
| Behaviour / support | `canManageStudents` | Assigned students |
| Communication | `communications.manage` | Own class / their parents |
| Calendar | `isStaff` | School calendar + own events |
| Resources | `isStaff` | Own / shared department |
| Department | `isStaff` | Own department |
| Leave / cover | `isStaff` | Self |
| Export | `reports.view` / `reports.generate` | Own class data |
| Notifications | `isStaff` | Own inbox |
| AI assistant | `isStaff` | Generators only |

## Demo login

After seeding (`php artisan db:seed` or `DemoDataSeeder`):

- Email: `teacher@school.co.zw`
- Password: `teacher123`

`TeacherPortalSeeder` loads sample lesson plans, syllabus, resources, LMS lessons, homework submissions, attendance, behaviour, leave/cover, notifications, and calendar events for that account.

## API hard rules

- Class/subject must be assigned (or active cover) before write.
- No school-wide student list without assignment filter.
- Parent contact only for students in assigned classes.
- Exam create/approve/publish remains exam officer / admin.
- Attendance unlock after lock is admin policy.
