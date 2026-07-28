# Production readiness checklist (shared-database SaaS)

This platform uses **one shared database** with `school_id` row-level tenancy.
Per-tenant databases are intentionally out of scope.

## Security gates

- `REQUIRE_TWO_FACTOR=true` in production (auto when `APP_ENV=production`)
- Privileged roles must enable 2FA via **Profile → Security** before using APIs
- Run `php artisan saas:go-live-check` before cutover

## Before go-live

1. **Environment**
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` set
   - `LICENSE_ENFORCEMENT=true` (or rely on production auto-enable)
   - `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`
   - `SESSION_SECURE_COOKIE=true` behind HTTPS
   - `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` match the SPA origin(s)

2. **Processes** (Docker Compose or Supervisor)
   - App (php-fpm) + nginx/apache
   - Queue worker: `php artisan queue:work --queue=webhooks,notifications,tenant,default`
   - Scheduler: `php artisan schedule:work` (or cron `* * * * * schedule:run`)
   - See root `supervisord.conf` and `docker-compose.yml`

3. **Tenant ingress**
   - Follow [wildcard-ingress.md](./wildcard-ingress.md)
   - Run `scripts/provision-wildcard-tls.sh` for `*.yourdomain`

4. **Payments & messaging**
   - Follow [paynow-webhooks-staging.md](./paynow-webhooks-staging.md)
   - Configure per-school SMS/WhatsApp credentials in School Settings (or platform env fallbacks)

5. **Health**
   - `GET /up` and platform System Health (`/platform/system/health`)
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
| Jobs | `BelongsToTenant` + `SetTenantContext` on `tenant` queue |
| Backups | Logical per-school JSON via `SchoolBackupService` |
| Migrations | Global Laravel migrations for the shared schema |
| Privileged 2FA | `2fa.enabled` middleware + Profile setup (production default) |
| Online exams | Student CBT under `/student/exams` + staff question bank |
| POS | Inventory till panel on Operations → Sales |
