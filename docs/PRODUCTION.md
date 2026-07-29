# Production go-live checklist

## Before first deploy

1. Set `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`.
2. Set `APP_URL` and `FRONTEND_URL` to HTTPS origins; enable `FORCE_HTTPS=true` and `SESSION_SECURE_COOKIE=true`.
3. Configure real mail (`MAIL_MAILER=smtp` or SES/Postmark) and run `php artisan queue:work`.
4. Set `LICENSE_ENFORCEMENT=true` for SaaS.
5. Keep `PAYMENT_GATEWAY_LIVE=false` until Paynow/EcoCash is fully integrated; use manual payments.
6. Set `AUDIT_ENABLED=true`.
7. Frontend build: `VITE_API_URL=https://your-api.example` (no trailing slash).
8. Seed production only:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=ProductionSeeder --force
   ```
   Do **not** run full `db:seed` in production (loads demo ERP data).
9. Cache: `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link`

## ProductionSeeder env

- `PROD_SCHOOL_NAME`, `PROD_SCHOOL_CODE`
- `PROD_ADMIN_EMAIL`, `PROD_ADMIN_PASSWORD`, `PROD_ADMIN_NAME`
- Optional: `PROD_SUPER_ADMIN_EMAIL`, `PROD_SUPER_ADMIN_PASSWORD`

## Online payments

`POST /api/v1/platform/payments/initiate` returns **503** while `PAYMENT_GATEWAY_LIVE=false`.
When enabling live gateway, also set `PAYMENT_GATEWAY_WEBHOOK_SECRET` and wire a signed webhook route before taking money.
