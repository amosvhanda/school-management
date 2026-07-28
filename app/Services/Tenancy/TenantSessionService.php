<?php

namespace App\Services\Tenancy;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class TenantSessionService
{
    public function applyForRequest(Request $request, School $school): void
    {
        if (! config('tenancy.session_partitioning', true)) {
            return;
        }

        $host = $this->resolveHost($request);

        if ($host === '' || in_array($host, $this->ignoredHosts(), true)) {
            return;
        }

        $baseCookie = (string) config('session.cookie');
        Config::set('session.cookie', "{$baseCookie}_s{$school->id}");

        if (! in_array($host, ['localhost', '127.0.0.1'], true)) {
            Config::set('session.domain', $host);
        }
    }

    private function resolveHost(Request $request): string
    {
        $candidates = [
            $request->headers->get('X-Forwarded-Host'),
            $request->headers->get('X-Original-Host'),
            $request->getHost(),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            return strtolower(trim(explode(',', $candidate)[0]));
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function ignoredHosts(): array
    {
        return array_values(array_filter(array_map(
            static fn ($host) => strtolower(trim((string) $host)),
            config('tenancy.ignored_hosts', ['localhost', '127.0.0.1']),
        )));
    }
}
