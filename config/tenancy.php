<?php

$appHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);

$defaultCentral = is_string($appHost) && $appHost !== '' && ! in_array($appHost, ['localhost', '127.0.0.1'], true)
    ? [$appHost]
    : [];

return [
    /*
    |--------------------------------------------------------------------------
    | Central application domains
    |--------------------------------------------------------------------------
    |
    | If the app is served from app.example.com, then school subdomains like
    | alpha.app.example.com can resolve to schools by slug/code fallback.
    |
    */
    'central_domains' => array_values(array_filter(array_map(
        static fn ($host) => strtolower(trim((string) $host)),
        explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', implode(',', $defaultCentral))),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Hosts that should never trigger tenant resolution
    |--------------------------------------------------------------------------
    */
    'ignored_hosts' => array_values(array_filter(array_map(
        static fn ($host) => strtolower(trim((string) $host)),
        explode(',', (string) env('TENANCY_IGNORED_HOSTS', 'localhost,127.0.0.1')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Tenant session cookie partitioning
    |--------------------------------------------------------------------------
    */
    'session_partitioning' => (bool) env('TENANCY_SESSION_PARTITIONING', true),

    /*
    |--------------------------------------------------------------------------
    | Current school id (set by queue middleware / request lifecycle)
    |--------------------------------------------------------------------------
    */
    'current_school_id' => null,
];
