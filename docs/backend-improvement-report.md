# Backend improvement report

**Date:** 2026-10-15  
**Companion:** [backend-audit.md](./backend-audit.md)

## Summary

The multi-tenant Laravel API already has strong isolation primitives (`SchoolScope`, `school.isolated`, portal guards, tenant jobs). Full suite baseline this re-audit: **525 passed** (2238 assertions). Prior same-day work hardened relation scopes, policies, Form Requests, and StudentController extraction. This continuation added fee-structure Form Requests, explicit school_id lookups (fee/timetable/teacher), IDOR coverage, and a Larastan config stub.

No Spatie Permission migration, no destructive migrations, and no API contract breaks.

---

## Modules inspected

- Platform / admin schools / licensing / tenancy services  
- Auth, 2FA, users, custom RBAC  
- Students, teachers, guardians, classes, attendance, exams, portals  
- Finance (fees, invoices, payments, payroll, enterprise journals)  
- Operations ERP (hostel, library, assets, transport, inventory, workflows)  
- Enterprise + platform controllers  
- Queue/ops (Horizon, Pulse, scheduler) — config reviewed; Redis runtime not exercised in this environment  

---

## Features verified as implemented

Evidence primarily from Feature tests + controller/service wiring:

- Tenant isolation for students, users, library loans, messages, jobs  
- Parent portal child scoping and denial paths  
- Capability/permission enforcement for teachers, finance, ops modules  
- Security hardening (temp passwords, finance vs student invoice access)  
- Core academic + finance + ERP smoke paths (`SchoolErpTest`, `EnterpriseErpTest`, etc.)  
- 2FA challenge + enforcement hooks  

## Partially implemented / missing

| Area | Notes |
|------|-------|
| Formal requirements tracker | Not in repo; audit is code/test-driven |
| Eloquent policies | Expanded: Invoice, Payment, Exam, JournalEntry (+ existing Student/Teacher/School/CustomField) |
| Form Requests | Exam, invoice/payment, enterprise finance, fee structures extracted; attendance/payroll still inline |
| Horizon/Pulse runtime | Packages + config present; staging Redis check still required |
| Enterprise/platform breadth | Large surface; not every endpoint has dedicated tests |
| AI assistant | Present with tools/stubs; treat as partial product surface |
| Larastan | `phpstan.neon.dist` (level 1) committed; package not installed (Packagist blocked in agent) |
| Fat controllers | `TeacherPortalController` ~1488 LOC; `StudentController` ~1169 LOC remaining |

## Confirmed bugs / defects and fixes

| Defect | Fix |
|--------|-----|
| Child models without school scope (`HostelBed`, `HostelAllocation`, `AssetDisposal`, `AssetMaintenanceLog`, `JournalLine`, `InstalmentScheduleItem`, `PurchaseRequisitionItem`, `WorkflowDefinitionStep`, `DocumentSignature`) could be queried across tenants if a code path omitted parent `whereHas` | Added `IsolatesViaSchoolRelation` (+ missing `asset()` / `student()` relations where needed) |
| `BelongsToSchool::resolveChildRouteBinding` ignored Laravel child relationship resolution | Removed override (default Laravel behavior restored) |
| Missing regression coverage for relation-scoped children | Added `TenantIsolationTest::test_child_models_without_school_id_are_isolated_via_relation` |

**Not changed (intentional):** Attendance upsert `orWhereNull('school_id')` for legacy row merge — still keyed by global `student_id`; covered by `AttendanceApiTest`.

---

## Security issues found and addressed

| Issue | Severity | Outcome |
|-------|----------|---------|
| Unscoped child Eloquent queries (defense-in-depth gap) | Medium | Fixed + tested |
| Incorrect child route binding override | Low | Fixed |
| Sparse policies / `$guarded = []` / fat controllers | Medium (process risk) | Documented; not blindly rewritten |

## Multi-tenant isolation findings

**Strengths:** `CurrentSchoolId` from auth user; fail-closed when authenticated without school; middleware blocks foreign `school_id`; SuperAdmin segregated from school APIs; parent access service; tenant job middleware.

**After this audit:** Relation-isolated children match the pattern already used by `LibraryLoan`, `HostelRoom`, `CommunicationMessage`, etc.

**Remaining watch list:** Platform endpoints that call `withoutSchoolScope` / `withoutGlobalScopes` must stay SuperAdmin-authorized; export/download and webhook paths deserve continued IDOR tests.

---

## Database & performance

- No schema migrations required for isolation fix (scopes are Eloquent-level).  
- Recommend follow-up: composite unique audits for school-scoped codes; index review on hot filters.  
- Prefer wrapping multi-step finance/lifecycle writes in `DB::transaction` (already done in several services).

---

## Files created / modified / removed

### Created

- `docs/backend-audit.md`  
- `docs/backend-improvement-report.md`  

### Modified / added (cumulative)

- Isolation + fillable on ERP/child models (hostel*, asset*, library*, transport*, consent*, visitors, vendors, jobs, etc.)  
- `HubMessageDelivery`, `SecureDocumentAccessLog` relation scopes  
- `app/Policies/InvoicePolicy.php`, `app/Policies/PaymentPolicy.php`  
- `app/Http/Controllers/Controller.php` (`AuthorizesRequests`)  
- `InvoiceController` / `PaymentController` policy authorization  
- `tests/Feature/Api/V1/TenantIsolationTest.php`  
- `tests/Feature/CrossTenantIdorTest.php`  
- Audit docs under `docs/`  

### Removed

- None (no migrations/tables/routes deleted)

### Packages added or changed

- **None in this audit pass.** Horizon/Pulse/Telescope were already present in `composer.json` from prior production-readiness work.

---

## Automated tests executed

| Command | Result |
|---------|--------|
| `php artisan test --filter='TenantIsolation\|TwoFactor\|UserAuthorization\|RolePermission\|SecurityHardening\|CapabilityMiddleware\|ParentChildren\|ParentPortal\|TenantRuntime'` | **65 passed** (261 assertions) |
| `php artisan test` (full suite baseline, before isolation fixes) | **515 passed** (2212 assertions) |
| `php artisan test --filter='TenantIsolation\|AttendanceApiTest'` (after fixes) | **25 passed** |
| `php artisan test --filter='SchoolErp\|EnterpriseErp\|WorkflowApi\|HelpDesk\|Procurement\|Recruitment\|Asset'` | **24 passed** |
| `php artisan test` (after first isolation fixes) | **516 passed** (2218 assertions) |
| `php artisan test` (after remaining audit fixes) | **521 passed** (2230 assertions) |
| `./vendor/bin/pint --dirty` | Fixed style on dirty PHP files |

### Remaining failures

None.

### Checks not completed

- Live Redis `php artisan horizon:status` / Pulse UI in a deployed environment  
- Manual SuperAdmin vs school-admin browser access to `/horizon` and `/pulse`  
- Larastan / PHPStan (not installed)

---

## Follow-up completed (same day)

| Item | Outcome |
|------|---------|
| Relation-scope `HubMessageDelivery`, `SecureDocumentAccessLog` | Done |
| Replace `$guarded = []` on ERP models with `$fillable` | Done (only `SchoolUserMembership` remains guarded by design) |
| `InvoicePolicy` + `PaymentPolicy`; wire `$this->authorize` | Done; base `Controller` now uses `AuthorizesRequests` |
| IDOR Feature suite | Added `tests/Feature/CrossTenantIdorTest.php` |
| `StudentController` split | Done for domain ops: report card, documents, ID cards, invoices, bulk actions, academic, photo (~1200 LOC controller remaining = CRUD + thin wrappers) |
| Horizon/Pulse SuperAdmin gates | Verified via `OpsDashboardAccessTest` |

## Follow-up completed (later same day)

| Item | Outcome |
|------|---------|
| `ExamPolicy` + `JournalEntryPolicy`; wire authorize | Done; IDOR coverage extended |
| Exam Form Requests (`Store`/`Update`/`RecordExamResults`) | Done |
| Invoice/Payment Form Requests + enterprise finance Form Requests | Done |
| Ops dashboard SuperAdmin gates | `OpsDashboardAccessTest` |

## Follow-up completed (re-audit continuation)

| Item | Outcome |
|------|---------|
| Full suite re-baseline | **525 passed** (2238 assertions) |
| Fee-structure Form Requests + explicit `school_id` on update/destroy | Done; IDOR test for cross-school update/delete |
| Timetable + Teacher update/destroy explicit `school_id` | Defense-in-depth (was relying on `SchoolScope` alone) |
| Larastan | Config stub `phpstan.neon.dist`; `composer require` blocked (Packagist/DNS in agent sandbox) — install locally |
| Packages added | **None** (Larastan require rolled back to keep lock/autoload healthy) |

### Commands executed (this continuation)

| Command | Result |
|---------|--------|
| `php artisan test` | **525 passed** |
| `php artisan test --filter='FeeStructureApi\|TimetableApi\|TeacherApiTest\|CrossTenantIdor\|…'` | **76 passed** |
| `composer require larastan/larastan` | **Failed** — Packagist CONNECT/DNS blocked in agent |
| `./vendor/bin/pint --dirty` | Fixed style on touched PHP files |

## Recommended next steps (priority order)

1. Merge / review [PR #6](https://github.com/amosvhanda/school-management/pull/6) into `feature-school-erp`.  
2. Locally: `composer require --dev larastan/larastan:^3.13` then `./vendor/bin/phpstan analyse` (uses `phpstan.neon.dist`).  
3. Staging: Redis + Horizon terminate-on-deploy + Pulse SuperAdmin gate verification.  
4. Continue Form Requests / service extraction for attendance, payroll, and teacher portal slices.  

---

## Note on concurrent WIP

Git status at audit start also included Horizon/Pulse/Telescope config, policy alignment, and frontend nav changes from the production-packages plan. Those were treated as in-progress product work and are **not** re-audited as defects of this pass; see `docs/deploy/production-readiness.md`.
