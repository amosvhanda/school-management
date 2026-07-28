<?php

namespace App\Jobs\Middleware;

use App\Models\School;
use App\Services\Tenancy\SchoolMailService;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;

class SetTenantContext
{
    public function __construct(private ?int $schoolId = null) {}

    /**
     * @param  object  $job
     * @param  Closure(object): mixed  $next
     */
    public function handle(object $job, Closure $next): mixed
    {
        $schoolId = $this->schoolId
            ?? (property_exists($job, 'schoolId') ? $job->schoolId : null)
            ?? (method_exists($job, 'tenantSchoolId') ? $job->tenantSchoolId() : null);

        if (! $schoolId) {
            return $next($job);
        }

        $school = School::query()->find((int) $schoolId);
        if (! $school) {
            return $next($job);
        }

        Context::add('tenant_school_id', $school->id);
        Context::add('tenant_school_code', $school->code);
        Config::set('tenancy.current_school_id', $school->id);

        app(SchoolMailService::class)->applyForSchool($school);

        try {
            return $next($job);
        } finally {
            Context::forget('tenant_school_id');
            Context::forget('tenant_school_code');
            Config::set('tenancy.current_school_id', null);
        }
    }
}
