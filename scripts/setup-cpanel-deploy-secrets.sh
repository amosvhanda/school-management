#!/usr/bin/env bash
# Push cPanel deploy secrets to GitHub Actions.
#
# Interactive:
#   ./scripts/setup-cpanel-deploy-secrets.sh
#
# From gitignored local env (preferred after first setup):
#   ./scripts/setup-cpanel-deploy-secrets.sh --from-env
#
# Requires: gh auth login (repo admin)

set -euo pipefail

cd "$(dirname "$0")/.."

if ! command -v gh >/dev/null 2>&1; then
  echo "Install GitHub CLI: https://cli.github.com/" >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "GitHub CLI auth is invalid. Run: gh auth login" >&2
  exit 1
fi

load_dotenv_file() {
  local file="$1"
  if [[ ! -f "$file" ]]; then
    echo "Missing $file" >&2
    exit 1
  fi
  # shellcheck disable=SC1090
  set -a
  source "$file"
  set +a
}

FROM_ENV=0
if [[ "${1:-}" == "--from-env" ]]; then
  FROM_ENV=1
  load_dotenv_file ".env.deploy.local"
fi

if [[ "$FROM_ENV" -eq 0 ]]; then
  read -r -p "CPANEL_FTP_HOST [e.g. 84.247.176.141]: " CPANEL_FTP_HOST
  read -r -p "CPANEL_FTP_USERNAME: " CPANEL_FTP_USERNAME
  read -r -s -p "CPANEL_FTP_PASSWORD (hidden): " CPANEL_FTP_PASSWORD
  echo
  read -r -p "CPANEL_FTP_REMOTE_DIR [school-management/]: " CPANEL_FTP_REMOTE_DIR
  CPANEL_FTP_REMOTE_DIR=${CPANEL_FTP_REMOTE_DIR:-school-management/}
  read -r -p "CPANEL_FTP_PORT [22]: " CPANEL_FTP_PORT
  CPANEL_FTP_PORT=${CPANEL_FTP_PORT:-22}
  read -r -p "VITE_API_URL [e.g. https://www.example.co.zw]: " VITE_API_URL
  read -r -p "APP_URL [defaults to VITE_API_URL]: " APP_URL
  APP_URL=${APP_URL:-$VITE_API_URL}
fi

: "${CPANEL_FTP_HOST:?CPANEL_FTP_HOST required}"
: "${CPANEL_FTP_USERNAME:?CPANEL_FTP_USERNAME required}"
: "${CPANEL_FTP_PASSWORD:?CPANEL_FTP_PASSWORD required}"
CPANEL_FTP_REMOTE_DIR=${CPANEL_FTP_REMOTE_DIR:-school-management/}
CPANEL_FTP_PORT=${CPANEL_FTP_PORT:-22}
APP_URL=${APP_URL:-${VITE_API_URL:-}}

gh secret set CPANEL_FTP_HOST --body "$CPANEL_FTP_HOST"
gh secret set CPANEL_FTP_USERNAME --body "$CPANEL_FTP_USERNAME"
gh secret set CPANEL_FTP_PASSWORD --body "$CPANEL_FTP_PASSWORD"
gh secret set CPANEL_FTP_REMOTE_DIR --body "$CPANEL_FTP_REMOTE_DIR"
gh secret set CPANEL_FTP_PORT --body "$CPANEL_FTP_PORT"

if [[ -n "${VITE_API_URL:-}" ]]; then
  gh secret set VITE_API_URL --body "$VITE_API_URL"
fi
if [[ -n "${APP_URL:-}" ]]; then
  gh secret set APP_URL --body "$APP_URL"
fi

echo "Secrets saved (SFTP port ${CPANEL_FTP_PORT}, remote ${CPANEL_FTP_REMOTE_DIR})."
echo "Trigger: Actions → deploy-cpanel → Run workflow, or push to main."
echo "Rotate the cPanel password if it was ever shared outside GitHub Secrets."
