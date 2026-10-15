# cPanel CI/CD (GitHub Actions → FTP)

Deploys this Laravel API + Vue SPA to shared cPanel hosting on every push to `main`, or via **Actions → deploy-cpanel → Run workflow**.

## Security first

- **Never** commit FTP/cPanel passwords to git or chat.
- Put credentials only in **GitHub → Settings → Secrets and variables → Actions**.
- If a password was pasted in chat, WhatsApp, or email screenshots: **change it in cPanel immediately**, then update the GitHub secret.

## Required GitHub Actions secrets

| Secret | Example / notes |
|--------|-----------------|
| `CPANEL_FTP_HOST` | Server IP / hostname (e.g. `84.247.176.141`). Port **2082** is the cPanel UI, not deploy. |
| `CPANEL_FTP_USERNAME` | cPanel account username |
| `CPANEL_FTP_PASSWORD` | cPanel password |
| `CPANEL_FTP_REMOTE_DIR` | Home-relative app path ending with `/`, e.g. `school-management/` (not `/school-management/` on this host) |
| `CPANEL_FTP_PORT` | Use **`22`** (SFTP). Plain FTP `:21` is often closed on this host. |
| `VITE_API_URL` | Public API origin baked into the SPA, e.g. `https://www.yourdomain.co.zw` |
| `APP_URL` | Site origin used to trigger the one-shot zip extract (defaults to `VITE_API_URL`) |

### How deploy works on this host

This cPanel account allows **SFTP** but **not SSH shell**, and Fileman has no extract API. The deployer therefore:

1. Stages `.deploy-bundle`
2. Builds one `deploy-bundle.zip`
3. Uploads the zip to `school-management/` over SFTP
4. Drops a self-deleting PHP extractor in `public_html/`
5. Calls `APP_URL/_sm_extract_….php?t=…&offset=…` in **chunks** (LiteSpeed would time out a single full unzip)
6. Verifies `artisan`, `vendor/autoload.php`, and `public/app/index.html` over SFTP

Existing `public_html` content is not replaced (only a temporary extractor file is added, then removed).

Verified end-to-end against the test host (SFTP zip upload + chunked HTTPS extract).

### Set secrets from local env

1. Ensure `.env.deploy.local` exists (gitignored) with the values above.
2. Fix GitHub CLI auth if needed: `gh auth login`
3. Push secrets:

```bash
./scripts/setup-cpanel-deploy-secrets.sh --from-env
```

Create `~/school-management/` once on the server (File Manager or SFTP mkdir) before the first Actions run — the workflow uses `sftp_only` and will not auto-create the remote folder.

### Local test deploy (this machine)

Credentials live in **gitignored** `.env.deploy.local` (never commit it).

```bash
bash scripts/stage-cpanel-bundle.sh
python3 -m pip install --user paramiko
python3 scripts/deploy-cpanel-local.py
```

Verified on the test host: **SFTP :22 works**, FTP :21 refused, full zip+chunked-extract deploy succeeded. Existing `public_html` is left alone; the app lives in `school-management/`.

**Note:** this account has SSH shell disabled. After deploy, run `php artisan migrate` / `config:cache` from **cPanel → Terminal** (or ask the host to enable SSH).

## One-time server setup (cPanel)

1. Create a **MySQL** database + user; note host, name, user, password.
2. Upload or create `.env` in the **app root** on the server (same folder as `artisan`), not inside git:

```env
APP_NAME="School Management"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...   # generate locally: php artisan key:generate --show
APP_URL=https://www.yourdomain.co.zw

LOG_CHANNEL=stack
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

FRONTEND_URL=https://www.yourdomain.co.zw
SANCTUM_STATEFUL_DOMAINS=www.yourdomain.co.zw,yourdomain.co.zw
```

3. Point the domain **document root** at the Laravel `public/` directory (cPanel → Domains → Document Root), **or** put only `public/` contents in `public_html` and set paths accordingly.
4. SPA build is published to `public/app/` by the workflow. Serve that from `/app/` (or map subdomain).
5. After first deploy, in **cPanel Terminal** (or SSH):

```bash
cd ~/path-to-app   # folder that contains artisan
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R ug+rwx storage bootstrap/cache
```

## Shared-hosting limits

This app prefers Redis + Horizon for queues. On basic cPanel:

- Use `QUEUE_CONNECTION=database` and a cron entry: `* * * * * php /home/USER/path-to-app/artisan schedule:run`
- Optional cron for workers: `* * * * * php /home/USER/path-to-app/artisan queue:work --stop-when-empty`
- Redis/Horizon usually need a VPS — see `docs/deploy/production-readiness.md`

## Manual trigger

GitHub → **Actions** → **deploy-cpanel** → **Run workflow**.

## Local dry-run (optional)

Build the same bundle the workflow stages:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
(cd school-system-frontend && npm ci && npm run build)
```

Do not FTP your local `.env`.
