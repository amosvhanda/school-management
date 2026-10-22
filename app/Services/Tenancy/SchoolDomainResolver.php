<?php

namespace App\Services\Tenancy;

use App\Models\School;
use App\Models\SchoolDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchoolDomainResolver
{
    public function resolveFromRequest(Request $request): ?School
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

            $host = trim(explode(',', $candidate)[0]);
            $school = $this->resolveFromHost($host);
            if ($school) {
                return $school;
            }
        }

        return null;
    }

    public function resolveFromHost(?string $host): ?School
    {
        $host = strtolower(trim((string) $host));

        if ($host === '' || in_array($host, $this->ignoredHosts(), true)) {
            return null;
        }

        $mapped = SchoolDomain::query()
            ->with('school')
            ->whereRaw('LOWER(domain) = ?', [$host])
            ->where('status', 'active')
            ->where('is_verified', true)
            ->first();

        if ($mapped?->school && $mapped->school->status === 'active') {
            return $mapped->school;
        }

        foreach ($this->centralDomains() as $baseDomain) {
            if ($host === $baseDomain || ! str_ends_with($host, '.'.$baseDomain)) {
                continue;
            }

            $subdomain = Str::beforeLast($host, '.'.$baseDomain);
            if ($subdomain === '' || str_contains($subdomain, '.')) {
                continue;
            }

            return $this->matchSchoolBySlug($subdomain);
        }

        return null;
    }

    private function matchSchoolBySlug(string $slug): ?School
    {
        $slug = Str::slug($slug);

        return School::query()
            ->where('status', 'active')
            ->get()
            ->first(function (School $school) use ($slug) {
                return Str::slug((string) $school->code) === $slug
                    || Str::slug((string) $school->name) === $slug;
            });
    }

    /**
     * @return list<string>
     */
    private function centralDomains(): array
    {
        return array_values(array_filter(array_map(
            static fn ($host) => strtolower(trim((string) $host)),
            config('tenancy.central_domains', []),
        )));
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
