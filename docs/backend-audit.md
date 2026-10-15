# Backend audit — School Management System

**Date:** 2026-10-15  
**Stack verified:** Laravel Framework 13.16.1, PHP 8.4.23, Sanctum, Fortify, custom RBAC (`PermissionService`), Horizon, Pulse, Telescope (dev)  
**API surface:** `/api/v1` — ~740 registered routes (`routes/api.php` → `routes/api/v1.php`)  
**Frontend:** Vue SPA in `school-system-frontend/` (Sanctum bearer / SPA cookie; Vite proxy in dev)

## Scope & requirements source

| Source | Status |
|--------|--------|
| Formal product requirements / backlog tracker in-repo | **Not found** as a single authoritative tracker |
| `PROJECT_CONTEXT.md` | Generated architectural dump (2026-10-06); useful inventory, not acceptance criteria |
| `docs/teacher-permissions.md` | Authoritative for teacher RBAC defaults |
| `docs/deploy/production-readiness.md` | Go-live ops + Horizon/Pulse decisions |
| Code + Feature tests | Primary evidence for “implemented and verified” |

**Limitation:** Module status below is assessed from routes, controllers, services, models, migrations, and automated tests — not from an external PRD. Features are not marked “Implemented and verified” unless exercised by tests or clearly wired end-to-end in code reviewed during this pass.

---

## Architecture snapshot

### Tenancy model

Shared database, row-level isolation via `school_id`.

| Layer | Mechanism | Paths |
|-------|-----------|-------|
| Context | `CurrentSchoolId` from authenticated user’s `school_id` (never client-trusted alone) | `app/Support/Tenancy/CurrentSchoolId.php` |
| Query scope | `BelongsToSchool` → `SchoolScope`; child rows via `IsolatesViaSchoolRelation` → `SchoolViaRelationScope` | `app/Models/Concerns/*`, `app/Models/Scopes/*` |
| HTTP | `school.isolated` (`ValidateSchoolIsolation`) rejects foreign `school_id` in body; SuperAdmin blocked from school APIs | `app/Http/Middleware/ValidateSchoolIsolation.php` |
| Domain | `ResolveSchoolFromDomain` on API stack | `bootstrap/app.php` |
| Jobs | `BelongsToTenant` + `SetTenantContext` | `app/Jobs/Concerns/BelongsToTenant.php`, `app/Jobs/Middleware/SetTenantContext.php` |
| Authz | Capability/permission middleware + `authorizeModuleAccess()` + few Eloquent policies | `PermissionService`, `EnsureCapability`, policies |

### Auth & roles

- **School users:** `User.school_id` + `UserRole` enum + DB `roles.permission_ids` / user overrides  
- **Platform:** `UserRole::SuperAdmin` — admin/platform prefixes only  
- **Portals:** Parent/Student limited by `staff.or.portal`; parent child access via `ParentAccessService`  
- **2FA:** Fortify + `2fa.enabled` middleware; production enforcement documented  

### Conventions observed

- Controllers call `authorizeModuleAccess(capabilities, permissionSlugs)` more often than Laravel policies  
- Shared CRUD via `ManagesSchoolResourceCrud`  
- Domain rules in `SchoolDomainRules` / dedicated services  
- Spatie Query Builder for list endpoints  
- JSON responses `{ data, message?, errors? }` with centralized exception rendering in `bootstrap/app.php`

---

## Multi-tenant security findings

### Verified controls (passing tests)

| Scenario | Evidence | Status |
|----------|----------|--------|
| Cross-school student show → 404 | `TenantIsolationTest` | Implemented and verified |
| `school_id` in body / nested → 403 | `TenantIsolationTest` | Implemented and verified |
| SuperAdmin cannot use school student APIs | `TenantIsolationTest` + middleware | Implemented and verified |
| Users list scoped | `TenantIsolationTest` | Implemented and verified |
| Library loans / communication messages scoped | `TenantIsolationTest` | Implemented and verified |
| Tenant jobs fail-closed without school id | `TenantIsolationTest` | Implemented and verified |
| Notification job does not process other school | `TenantIsolationTest` | Implemented and verified |
| Parent cannot access unlinked / other-school child | `ParentPortalApiTest`, `ParentChildrenApiTest` | Implemented and verified |
| Role/capability denial for teachers/finance | `RolePermissionEnforcementTest`, `SecurityHardeningPhase*` | Implemented and verified |
| Relation-scoped child models (hostel beds/allocations, asset disposals) | `TenantIsolationTest::test_child_models_without_school_id_are_isolated_via_relation` | Implemented and verified (hardened this audit) |

### Risks / gaps (evidence-based)

| ID | Finding | Severity | Status | Action |
|----|---------|----------|--------|--------|
| T1 | Several child tables lacked `IsolatesViaSchoolRelation` (`HostelBed`, `HostelAllocation`, `AssetDisposal`, `AssetMaintenanceLog`, `JournalLine`, `InstalmentScheduleItem`, `PurchaseRequisitionItem`, `WorkflowDefinitionStep`, `DocumentSignature`, later `HubMessageDelivery`, `SecureDocumentAccessLog`). Controllers often filtered via parents, but raw model queries were unscoped. | Medium | **Fixed** | Traits + relationships + regression tests |
| T2 | `BelongsToSchool::resolveChildRouteBinding` queried the parent model class without relationship resolution (incorrect vs Laravel default). No nested bindings found in routes today, but override was unsafe. | Low | **Fixed** | Removed override |
| T3 | Sparse Eloquent policies vs ~191 models. Most authz is middleware + `authorizeModuleAccess`. | Medium | **Improved** | Added `InvoicePolicy`, `PaymentPolicy`, `ExamPolicy`, `JournalEntryPolicy`; base Controller uses `AuthorizesRequests` |
| T4 | ~25 models used `$guarded = []`. Mass-assignment risk if a future controller uses `$request->all()`. | Medium | **Fixed** | ERP models switched to `$fillable`; only `SchoolUserMembership` remains guarded |
| T5 | `authorizeModuleAccess` short-circuits for SuperAdmin. Mitigated by `ValidateSchoolIsolation` blocking school paths; platform routes rely on `super_admin` middleware. | Low | Accepted | Keep middleware order; do not remove SuperAdmin gate casually |
| T6 | Attendance upsert matches `school_id` **or null** for the same `student_id` (legacy merge). Cross-tenant safe while student IDs are global; do not drop without data migration. | Low | Intentional | Documented in controller comment |
| T7 | Platform/group models (`GroupPolicy`, `CrossSchoolTransfer`, `LicenseKey`, `HubMessageDelivery`, etc.) intentionally lack school scopes — must stay behind SuperAdmin / platform services. | Info | By design | Keep reviewing platform controllers |
| T8 | Few Form Requests vs many inline `$request->validate` / `Validator::make`. Consistency and reuse suffer; not automatically insecure. | Low | **Improved** | Exam + invoice/payment + enterprise finance + fee-structure writes use Form Requests; continue for attendance/payroll |
| T9 | Some controllers used `Model::findOrFail($id)` relying only on `SchoolScope` (fee structures, timetable, teachers). Safe while `CurrentSchoolId` is set; brittle if scope is bypassed. | Low | **Fixed** | Explicit `where('school_id', …)` on update/destroy; fee-structure cross-tenant IDOR test added |
| T10 | Fat controllers remain: `TeacherPortalController` (~1488 LOC), `StudentController` (~1169), `DashboardController`, portals. | Medium (maintainability) | Open | Extract further slices when touching those modules |
| T11 | Larastan / PHPStan | Info | **Done (level 1 + baseline)** | `larastan/larastan` ^3.13 installed; `phpstan.neon.dist` + `phpstan-baseline.neon` (~424 known issues). Shrink baseline over time. |

### Explicitly out of scope (per product decision)

- Migrating to `spatie/laravel-permission`  
- Paynow / SMS go-live (deferred)  
- Full Telescope in production  

---

## Module inventory

Status key: **Implemented and verified** | **Partially implemented** | **Not implemented** | **Broken** | **Blocked**

### Platform & tenancy

| Feature | Implementation | Verified functionality | Missing / risks | Tests | Status | Action |
|---------|----------------|------------------------|-----------------|-------|--------|--------|
| School provisioning / admin schools | `AdminSchoolController`, `SchoolProvisioningService` | Create/list/status/domains/backups | — | `AdminSchoolProvisionTest`, `Tenant*` | Implemented and verified | Maintain |
| Domain resolution | `ResolveSchoolFromDomain`, `SchoolDomainResolver` | Domain → school context | Ops TLS/wildcard | `TenantDomainResolutionTest` | Implemented and verified | Follow deploy docs |
| License enforcement | `EnsureSchoolLicenseActive`, `LicenseService` | Blocks when enforcement on | — | `SchoolLicenseTest`, `TenantRuntimeIsolationTest` | Implemented and verified | Keep `LICENSE_ENFORCEMENT` on in prod |
| Tenant switch / session / cache / mail / storage | `Services/Tenancy/*` | Covered by tenant tests | — | `TenantSwitchTest`, `TenantSessionTest`, `TenantMailAndCacheTest`, `MessagingAndStorageTest` | Implemented and verified | Maintain |
| Horizon / Pulse / scheduler hardening | Config + providers + `routes/console.php` | Config present; ops-dependent | Runtime Redis required | `TwoFactorEnforcementTest` (go-live check) | Partially implemented | Verify `horizon:status` in staging |

### Auth, users, RBAC

| Feature | Implementation | Verified | Gaps | Tests | Status | Action |
|---------|----------------|----------|------|-------|--------|--------|
| Login / me / logout / password | `AuthController`, Fortify | Capabilities + permissions in payload | — | `AuthTest`, `AuthApiTest`, `RolePermissionEnforcementTest` | Implemented and verified | Maintain |
| 2FA | `TwoFactorAuthController` | Challenge + enforcement | — | `TwoFactorAuthApiTest`, `TwoFactorEnforcementTest` | Implemented and verified | Prod `REQUIRE_TWO_FACTOR` |
| Users / roles / permissions | `UserController`, `RoleController`, `PermissionController`, `PermissionService` | Overrides, denial paths | Policy coverage thin | `UserAuthorizationTest`, `RolePermission*` | Implemented and verified | Align remaining role-only checks with capabilities |
| Temporary password / must-change | Auth services + middleware | — | — | `SecurityHardeningPhase1Test` | Implemented and verified | Maintain |

### Academic core

| Feature | Paths | Notes | Tests | Status |
|---------|-------|-------|-------|--------|
| Students CRUD / documents / photos / ID card | `StudentController` (~1169 LOC) + `Students/*` services, `StudentPolicy` | CRUD remains in controller; domain ops extracted | `StudentApiTest`, photo/doc/id tests | Implemented and verified |
| Student lifecycle / placement / promotion | `StudentLifecycleController`, services | Status transitions + hostel/transport end | `StudentLifecycleTest`, `StudentPlacementLifecycleTest` | Implemented and verified |
| Classes, subjects, grade levels, terms, rooms, departments | Controllers + `ManagesSchoolResourceCrud` | Capability-gated | `ClassApiTest`, `DepartmentApiTest`, `AcademicApiTest` | Implemented and verified |
| Teachers / assignments | `TeacherController`, `TeacherAssignmentController`, `TeacherPolicy` | Portal vs staff split | `TeacherApiTest`, `TeacherRelationshipsTest`, `TeacherPermissionsTest` | Implemented and verified |
| Guardians / parent links | `GuardianController`, `GuardianService` | Pivot sync | `GuardianApiTest`, `ParentStudentRelationshipsTest` | Implemented and verified |
| Timetable | `TimetableController`, generator/conflict services | — | `TimetableApiTest` | Implemented and verified |
| Attendance | `AttendanceController` | Lock/force/legacy merge | `AttendanceApiTest` | Implemented and verified |
| Exams / grades / schedules / CBT | Controllers + student portal | Enterprise exam extras separate | `ExamApiTest`, `StudentPortalCbtTest`, `SchoolAssessmentApiTest` | Implemented and verified |
| Assignments / LMS-ish | `AssignmentController`, portals | — | `StudentPortalAssignmentsTest` | Partially implemented (depth varies by portal) |

### Finance

| Feature | Paths | Notes | Tests | Status |
|---------|-------|-------|-------|--------|
| Fee structures / categories / groups / discounts | Fee* controllers, services | — | `FeeStructureApiTest`, `InvoiceDiscountAndFeeGroupTest` | Implemented and verified |
| Invoices / payments / transactions | Controllers + ledger services | Webhooks env-gated | `InvoiceApiTest`, `PaymentApiTest`, `TransactionApiTest` | Implemented and verified |
| Payroll | `PayrollController` | — | `PayrollApiTest` | Implemented and verified |
| Cash flow / budgets / enterprise journals | Finance + enterprise services | Account IDs school-scoped in validation | `CashFlowApiTest`, `EnterpriseErpTest`, `AlumniBudgetCalendarModulesTest` | Implemented and verified |
| Spend / procurement | `ProcurementController` | Workflow-backed | `SpendRequestApiTest`, `SchoolErpTest` | Implemented and verified |

### Operations / ERP

| Feature | Paths | Tests | Status |
|---------|-------|-------|--------|
| Hostel / transport / library / visitors / assets / inventory | Controllers + domain rules | `SchoolErpTest`, role denial tests | Implemented and verified |
| Holiday programs / school trips | Controllers + services | Parent portal enrollments | Implemented and verified |
| Help desk / recruitment / compliance / consent | Controllers | Dedicated feature tests | Implemented and verified |
| Workflows | `WorkflowController`, `WorkflowService` | `WorkflowApiTest` | Implemented and verified |
| Communications / announcements | Controllers + messaging services | `CommunicationMessagesTest` | Implemented and verified |
| Certificates / uploads / imports | Controllers + import service | `PeopleCsvImportTest`, certificate tests | Implemented and verified |

### Portals

| Feature | Paths | Tests | Status |
|---------|-------|-------|--------|
| Parent portal | `ParentPortalController`, `ParentAccessService` | Strong isolation coverage | Implemented and verified |
| Teacher portal | `TeacherPortalController` | `TeacherPortalApiTest` | Implemented and verified |
| Student portal | `StudentPortalController` | Assignments + CBT tests | Implemented and verified |

### Enterprise / platform intelligence

| Feature | Paths | Tests | Status |
|---------|-------|-------|--------|
| Enterprise academic/finance/exam/governance/intelligence | `Enterprise/*` controllers + services | `EnterpriseErpTest`, `AdvancedPlatformTest` | Partially implemented — breadth large; rely on smoke + targeted tests |
| Platform ops / finance / docs / hub messaging | `Platform/*` | Platform tests + SuperAdmin middleware | Partially implemented |
| AI assistant | `AssistantController`, `laravel/ai` | `AssistantTest` | Partially implemented (stub/tooling present) |

### Observability & jobs

| Feature | Paths | Status |
|---------|-------|--------|
| Horizon | `config/horizon.php`, providers, supervisord | Partially implemented — needs Redis runtime verification |
| Pulse | `config/pulse.php`, dashboard view | Partially implemented |
| Telescope | dev package, env-gated | Implemented for local/staging only (by design) |
| Scheduled prune / snapshot / penalties | `routes/console.php` | Implemented and verified (code); ops must run scheduler |

---

## Database & performance

| Topic | Finding |
|-------|---------|
| Migrations | ~97 migration files; shared schema with pervasive `school_id` FKs on tenant tables |
| Child tables without `school_id` | Hostel beds/allocations, journal lines, etc. — isolation via parent relation (now scoped in Eloquent) |
| Unique constraints | Mix of global and `(school_id, …)` composites; review when adding codes (fee codes, asset tags, etc.) |
| Indexes | Most FKs constrained; recommend periodic `EXPLAIN` on hot lists (students, attendance, invoices) |
| N+1 | Some list endpoints use `with()`; fat controllers may still over-query under includes |
| Transactions | Present on attendance upsert, hostel allocate, journal post, lifecycle exits — good pattern to extend |
| Soft deletes | Not universal; confirm delete semantics per module before relying on restore |

---

## Code quality (architecture)

| Issue | Evidence | Recommendation |
|-------|----------|----------------|
| Fat `StudentController` (~2800 LOC) | File size | Extract actions/services gradually; preserve API contracts |
| Authz dual paths | Middleware + `authorizeModuleAccess` + sparse policies | Standardize; expand policies for Student/Teacher/Invoice/Payment |
| Inline validation | Few Form Requests | Add requests for multi-step finance/lifecycle writes |
| `$guarded = []` | 25 models | Tighten fillable on write-heavy models |
| Duplicate controller basenames | `StudentController` / `TeacherController` under both root and `Api/V1` | Prefer single namespace over time |
| Jobs count | Only 4 job classes | Many “async” paths may still be sync; OK for now with Horizon queues reserved |

---

## Testing & static analysis

| Tool | Present? | Result this audit |
|------|----------|-------------------|
| PHPUnit 12 | Yes | After remaining fixes: full suite **521 passed** (see improvement report for latest count) |
| Laravel Pint | Yes (`vendor/bin/pint`) | Ran `--dirty` on changed files |
| Larastan / PHPStan | **Not configured** | Not installed; skip rather than add incompatible tooling mid-audit |
| Pest | No | N/A |

### Commands executed

```bash
php artisan test --filter='TenantIsolation|TwoFactor|UserAuthorization|RolePermission|SecurityHardening|CapabilityMiddleware|ParentChildren|ParentPortal|TenantRuntime'
# → 65 passed

php artisan test
# → 515 passed (2212 assertions)

php artisan test --filter='TenantIsolation|AttendanceApi|SchoolErp|EnterpriseErp|Hostel|Asset|WorkflowApi|StudentLifecycle'
# → failures fixed; re-run TenantIsolation|AttendanceApiTest → 25 passed

php artisan test --filter='SchoolErp|EnterpriseErp|WorkflowApi|HelpDesk|Procurement|Recruitment|Asset'
# → 24 passed

./vendor/bin/pint --dirty
```

---

## Recommended corrective actions (priority)

1. **P0 — Done:** Relation-scope unscoped child models + regression test; remove unsafe `resolveChildRouteBinding`.  
2. **P1 — Done:** IDOR tests (`CrossTenantIdorTest`) for invoices, payments, hostel allocate, results download, exams/journals.  
3. **P1 — Done:** Invoice/Payment/Exam/Journal policies + `$fillable` hardening on ERP models.  
4. **P1 — Done:** Split `StudentController` into domain services (report card, documents, ID card, invoices, bulk, academic, photo).  
5. **P2 — Done:** Form Requests for exam CRUD/results, journal, invoice/payment, enterprise finance, and fee-structure writes.  
6. **P2 — Done:** Larastan level 1 + baseline; attendance/payroll Form Requests.  
7. **P3:** Staging verification of Horizon/Pulse with Redis; SuperAdmin-only UI access.  
8. **P3:** Continue thinning `TeacherPortalController`; shrink PHPStan baseline.  

---

## Change log (this audit)

See `docs/backend-improvement-report.md` for files modified, test results after fixes, and remaining risks.
