# Wildcard ingress for multi-tenant subdomains

School tenants resolve from `{school-code}.{CENTRAL_DOMAIN}`. The app already maps host → school; ingress must terminate TLS and route all tenant hosts to the Laravel API.

## DNS

1. Create an `A` or `CNAME` record for `*.CENTRAL_DOMAIN` pointing to your load balancer / VPS.
2. Optionally create `CENTRAL_DOMAIN` for the marketing or admin SPA.

Example:

```
*.schoolerp.example   A     203.0.113.10
schoolerp.example     A     203.0.113.10
```

## TLS (Let's Encrypt wildcard)

Wildcard certificates require a DNS-01 challenge. Use the helper script:

```bash
CENTRAL_DOMAIN=schoolerp.example \
CERTBOT_DNS_PLUGIN=cloudflare \
CERTBOT_DNS_CREDENTIALS=/etc/letsencrypt/cloudflare.ini \
./scripts/provision-wildcard-tls.sh
```

Set `CERTBOT_DNS_PLUGIN` to your provider (`cloudflare`, `route53`, `digitalocean`, etc.).

## Nginx (HTTPS catch-all)

Add a second server block alongside `nginx.conf`:

```nginx
server {
    listen 443 ssl http2;
    server_name _;

    ssl_certificate     /etc/letsencrypt/live/schoolerp.example/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/schoolerp.example/privkey.pem;

    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass app:9000;
    }
}
```

The Laravel `SchoolDomainResolver` reads `Host` / `X-Forwarded-Host`, so no per-tenant nginx vhosts are required.

## Apache

Use `apache.conf` catch-all `ServerAlias *.schoolerp.example` with `SSLEngine on` and the same certificate paths.

## Renewal

Add a cron entry:

```cron
0 3 * * * certbot renew --quiet && nginx -s reload
```

## Verify

```bash
curl -I https://demo.schoolerp.example/api/v1/health
```

Custom domains continue to use per-domain DNS TXT verification in **Platform → School domains**.
