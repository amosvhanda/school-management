#!/usr/bin/env bash
set -euo pipefail

# Provision wildcard TLS for tenant subdomains using Certbot DNS challenge.
# Requires: certbot, a DNS provider plugin (e.g. certbot-dns-cloudflare), and API credentials.
#
# Usage:
#   CENTRAL_DOMAIN=schoolerp.example WILDCARD=*.schoolerp.example ./scripts/provision-wildcard-tls.sh
#
# After issuance, point nginx/apache to:
#   /etc/letsencrypt/live/${CENTRAL_DOMAIN}/fullchain.pem
#   /etc/letsencrypt/live/${CENTRAL_DOMAIN}/privkey.pem

CENTRAL_DOMAIN="${CENTRAL_DOMAIN:-schoolerp.example}"
WILDCARD="${WILDCARD:-*.${CENTRAL_DOMAIN}}"
EMAIL="${CERTBOT_EMAIL:-admin@${CENTRAL_DOMAIN}}"

if ! command -v certbot >/dev/null 2>&1; then
  echo "certbot is required. Install certbot and your DNS provider plugin first." >&2
  exit 1
fi

echo "Requesting certificate for ${CENTRAL_DOMAIN} and ${WILDCARD}..."

certbot certonly \
  --non-interactive \
  --agree-tos \
  --email "${EMAIL}" \
  -d "${CENTRAL_DOMAIN}" \
  -d "${WILDCARD}" \
  ${CERTBOT_DNS_PLUGIN:+--dns-${CERTBOT_DNS_PLUGIN}} \
  ${CERTBOT_DNS_CREDENTIALS:+--dns-${CERTBOT_DNS_PLUGIN}-credentials "${CERTBOT_DNS_CREDENTIALS}"} \
  ${CERTBOT_DNS_PROPAGATION_SECONDS:+--dns-${CERTBOT_DNS_PLUGIN}-propagation-seconds "${CERTBOT_DNS_PROPAGATION_SECONDS}"}

echo "Certificate stored under /etc/letsencrypt/live/${CENTRAL_DOMAIN}/"
echo "Reload nginx/apache after updating SSL server blocks."
