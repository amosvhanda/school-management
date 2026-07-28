<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\SchoolDomainResolver;
use App\Services\Tenancy\TenantSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchoolFromDomain
{
    public function __construct(private SchoolDomainResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $school = $this->resolver->resolveFromRequest($request);

        if ($school) {
            $request->attributes->set('currentSchool', $school);
            app()->instance('currentSchool', $school);
            app(TenantSessionService::class)->applyForRequest($request, $school);
        }

        return $next($request);
    }
}
