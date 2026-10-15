<?php

namespace App\Jobs\Middleware;

use App\Jobs\Concerns\BelongsToTenant;
use App\Models\School;
use App\Services\Tenancy\SchoolMailService;
use InvalidArgumentException;
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

        $requiresTenant = in_array(BelongsToTenant::class, class_uses_recursive($job), true);

        if ($requiresTenant && (! is_numeric($schoolId) || (int) $schoolId < 1)) {
            throw new InvalidArgumentException('Tenant job is missing school_id.');
        }

        if (! $schoolId) {
            return $next($job);
        }

        $school = School::query()->find((int) $schoolId);
        if (! $school) {
            throw new InvalidArgumentException("Tenant job school {$schoolId} was not found.");
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
