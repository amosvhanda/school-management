# Production readiness checklist (shared-database SaaS)

This platform uses **one shared database** with `school_id` row-level tenancy.
Per-tenant databases are intentionally out of scope.

## Security gates

- `REQUIRE_TWO_FACTOR=true` in production (auto when `APP_ENV=production`)
- Privileged roles must enable 2FA via **Profile → Security** before using APIs
- Run `php artisan saas:go-live-check` before cutover

## Permissions (keep custom RBAC)

Do **not** migrate to `spatie/laravel-permission` for go-live. Authorization uses:

- Catalog: `config/permissions.php` + `config/permission_capabilities.php`
- Resolution: `App\Services\PermissionService`
- Enforcement: `capability` / `permission` middleware + policies

School isolation stays outside RBAC (`school.isolated`, scopes, policy ownership checks).

## Before go-live

1. **Environment**
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set
   - `LICENSE_ENFORCEMENT=true` (or rely on production auto-enable)
   - `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`
   - `SESSION_SECURE_COOKIE=true` behind HTTPS
   - `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` match the SPA origin(s)

2. **Processes** (Docker Compose or Supervisor)
   - App (php-fpm) + nginx/apache
   - Queue: `php artisan horizon` (requires Redis; queues `webhooks,notifications,tenant,default`)
   - On deploy: `php artisan horizon:terminate` so workers restart cleanly
   - Scheduler: `php artisan schedule:work`
   - See root `supervisord.conf` and `docker-compose.yml`
   - Dashboards (SuperAdmin): `/horizon`, `/pulse`
   - Telescope (`/telescope`): local/staging only (`TELESCOPE_ENABLED=true`); not installed with `composer install --no-dev`

3. **Tenant ingress**
   - Follow [wildcard-ingress.md](./wildcard-ingress.md)
   - Run `scripts/provision-wildcard-tls.sh` for `*.yourdomain`

4. **Payments & messaging** *(deferred — not required for current go-live)*
   - Paynow and SMS/WhatsApp are optional until you enable them
   - When ready: follow [paynow-webhooks-staging.md](./paynow-webhooks-staging.md) and set per-school SMS/WhatsApp credentials

5. **Health**
   - `GET /up` and platform System Health (`/platform/system/health`)
   - Horizon + Pulse for queue/runtime metrics
   - Confirm failed jobs / notification queue depth are acceptable
   - `php artisan saas:go-live-check` (use `--strict` before production cutover)

6. **Database pooling (optional ops)**
   - Place PgBouncer or Cloud SQL Auth Proxy in front of MySQL/Postgres
   - App keeps a single shared connection name — pooling is infrastructure

## CSRF note

Bearer-token API clients do not use cookie CSRF. Sanctum SPA cookie auth should call
`GET /sanctum/csrf-cookie` before state-changing requests from the SPA origin.

## Architecture decision

| Concern | Approach |
|---------|----------|
| Tenant isolation | `SchoolScope` + `school.isolated` + membership checks |
| Jobs | `BelongsToTenant` + `SetTenantContext`; Horizon on Redis |
| Observability | Pulse + Horizon (SuperAdmin); Telescope local/staging only |
| Permissions | Custom `PermissionService` (not Spatie) |
| Backups | Logical per-school JSON via `SchoolBackupService` |
| Migrations | Global Laravel migrations for the shared schema |
| Privileged 2FA | `2fa.enabled` middleware + Profile setup (production default) |
| Online exams | Student CBT under `/student/exams` + staff question bank |
| POS | Inventory till panel on Operations → Sales |

## Local verification log

| Gate | Result | Notes |
|------|--------|-------|
| `php artisan test` | **515 passed** (2212 assertions) | Full suite green (pre-Horizon) |
| `school-system-frontend` `npm run build` | **green** | `vue-tsc -b && vite build` |
| `php artisan saas:go-live-check` | **green** | Local/non-production skips strict prod env asserts |
| Horizon + Pulse packages | installed | Horizon manually registered (Laravel 13 DevCommands) |
| Telescope | installed (--dev) | Disabled by default; local/staging opt-in |
| Staging env + Redis/HTTPS/2FA | pending | Needs staging host |
| `saas:go-live-check --strict` on staging | pending | Run after staging env mirrors production |
| Paynow + SMS/WhatsApp staging | deferred | Not in current go-live scope |
| Wildcard TLS + subdomain smoke | pending | See wildcard-ingress.md |
| Production cutover | blocked | After staging drill passes |
