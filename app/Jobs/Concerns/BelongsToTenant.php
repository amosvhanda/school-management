<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\SetTenantContext;

trait BelongsToTenant
{
    public ?int $schoolId = null;

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            new SetTenantContext($this->schoolId),
        ];
    }

    public function tenantSchoolId(): ?int
    {
        return $this->schoolId;
    }
}
