<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAuditContext
{
    public function __construct(private AuditService $auditService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->auditService->setRequest($request);

        return $next($request);
    }
}
