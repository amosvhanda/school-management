#!/usr/bin/env bash
# Build production artifacts and stage .deploy-bundle for FTP upload.
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ -f .env.deploy.local ]]; then
  # shellcheck disable=SC1091
  set -a
  source .env.deploy.local
  set +a
fi

if [[ "${SKIP_BUILD:-0}" != "1" ]]; then
  echo "==> Composer (no-dev)"
  composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

  echo "==> Laravel Vite assets"
  if [[ -f package-lock.json ]]; then npm ci; else npm install; fi
  npm run build

  echo "==> Vue SPA"
  (
    cd school-system-frontend
    if [[ -f package-lock.json ]]; then npm ci; else npm install; fi
    VITE_API_URL="${VITE_API_URL:-}" npm run build
  )
else
  echo "==> SKIP_BUILD=1 — using existing vendor/ / build outputs"
fi

echo "==> Stage .deploy-bundle"
rm -rf .deploy-bundle
mkdir -p .deploy-bundle

rsync -a \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='.cursor/' \
  --exclude='.vscode/' \
  --exclude='.idea/' \
  --exclude='.aider*' \
  --exclude='.scribe/' \
  --exclude='.DS_Store' \
  --exclude='.deploy-bundle/' \
  --exclude='.deploy-bundle.zip' \
  --exclude='.dockerignore' \
  --exclude='node_modules/' \
  --exclude='school-system-frontend/' \
  --exclude='tests/' \
  --exclude='Dockerfile' \
  --exclude='docker-compose.yml' \
  --exclude='nginx.conf' \
  --exclude='apache*.conf' \
  --exclude='supervisord.conf' \
  --exclude='generate_context.sh' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='!.env.example' \
  --exclude='database/*.sqlite' \
  --exclude='docs/' \
  --exclude='*.md' \
  --exclude='tmp-*' \
  --exclude='.phpunit*' \
  --exclude='phpunit.xml' \
  --exclude='phpstan*' \
  --exclude='_ide_helper*' \
  ./ .deploy-bundle/

mkdir -p .deploy-bundle/public/app
if [[ -d school-system-frontend/dist ]]; then
  rsync -a school-system-frontend/dist/ .deploy-bundle/public/app/
fi

mkdir -p \
  .deploy-bundle/storage/framework/{cache,sessions,views} \
  .deploy-bundle/storage/logs \
  .deploy-bundle/bootstrap/cache

rm -f .deploy-bundle/.env .deploy-bundle/school-system-frontend/.env .deploy-bundle/.env.deploy.local

du -sh .deploy-bundle
echo "Bundle ready. Next: python3 scripts/deploy-cpanel-local.py"
