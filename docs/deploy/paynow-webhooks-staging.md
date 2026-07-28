# Paynow & webhooks — staging setup

Use this runbook when enabling online payments and enterprise webhooks on a staging environment.

## 1. Environment

Copy from `.env.example` and set:

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Public API base URL (required for Paynow callbacks) |
| `PAYMENTS_MODE` | `sandbox` for testing, omit or use per-school `live` credentials for real Paynow |
| `PAYNOW_RESULT_URL` | Optional override if `APP_URL` differs from the URL Paynow can reach |
| `PAYNOW_TIMEOUT` | HTTP timeout for Paynow API calls (default 30) |

Verify URLs:

```bash
php artisan payments:staging-info
```

Register the printed **Paynow result URL** in your Paynow merchant dashboard.

## 2. Per-school Paynow credentials

Store encrypted credentials via the platform API (school admin / platform finance):

```http
POST /api/v1/platform/payment-gateways
Authorization: Bearer {token}
Content-Type: application/json

{
  "provider": "paynow",
  "is_active": true,
  "supports_mobile_money": true,
  "credentials": {
    "mode": "live",
    "integration_id": "YOUR_ID",
    "integration_key": "YOUR_KEY"
  }
}
```

For sandbox testing without live keys, set `"mode": "sandbox"` or leave global `PAYMENTS_MODE=sandbox`. Students can complete payment via the sandbox checkout redirect.

## 3. Inbound Paynow webhook

| Method | URL |
|--------|-----|
| `POST` | `/api/v1/webhooks/payments/paynow` |

Paynow sends form-encoded fields including `hash`. The app verifies SHA-512 using the school's `integration_key` from the transaction's gateway config.

Test locally with the feature suite:

```bash
php artisan test --filter=PaynowLiveGateway
```

## 4. Enterprise outbound webhooks

Create a subscription (secret shown once):

```http
POST /api/v1/enterprise/integrations/webhooks
Authorization: Bearer {token}

{
  "event": "payment.completed",
  "target_url": "https://your-app.example/webhooks/school",
  "is_active": true
}
```

Deliveries are signed with `X-Webhook-Signature` (HMAC-SHA256). Failed deliveries can be retried:

```bash
php artisan webhooks:retry-failed
php artisan webhooks:retry-failed --school=1 --limit=20
```

The scheduler retries failed deliveries every 15 minutes when `php artisan schedule:work` is running.

## 5. Queue worker

Outbound webhooks and notifications require a queue worker:

```bash
php artisan queue:work --queue=webhooks,notifications,default
```

In production/staging, run this under Supervisor or your process manager (see `supervisord.conf`).

## 6. OpenAPI publish

After API changes:

```bash
php artisan openapi:publish
cd school-system-frontend && npm run types:api
```

## 7. Smoke test checklist

- [ ] `php artisan payments:staging-info` shows public URLs (not localhost)
- [ ] Initiate payment from student fees UI → sandbox checkout or Paynow redirect
- [ ] Paynow callback marks invoice paid (`payment.completed` webhook fires)
- [ ] Enterprise webhook delivery appears in `/api/v1/enterprise/integrations/webhooks/deliveries`
- [ ] Failed delivery retries via `webhooks:retry-failed`
