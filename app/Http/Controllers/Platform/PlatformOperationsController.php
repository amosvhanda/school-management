<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ResolvesPlatformSchoolScope;
use App\Models\ApiClient;
use App\Models\OperationsAlert;
use App\Services\Platform\AuditIntegrityService;
use App\Services\Platform\ExternalApiService;
use App\Services\Platform\OperationsDashboardService;
use App\Services\Platform\PredictiveAnalyticsService;
use App\Services\Platform\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformOperationsController extends Controller
{
    use ResolvesPlatformSchoolScope;

    public function __construct(
        private OperationsDashboardService $operations,
        private PredictiveAnalyticsService $predictive,
        private SystemHealthService $health,
        private AuditIntegrityService $auditIntegrity,
        private ExternalApiService $externalApi,
    ) {}

    private function authorizePlatformOps(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['operations.manage', 'settings.manage'],
        );
    }


    public function liveDashboard(Request $request)
    {
        $this->authorizePlatformOps($request);

        return response()->json(['data' => $this->operations->liveFeed($this->platformSchoolId($request))]);
    }

    public function resolveAlert(Request $request, int $id)
    {
        $this->authorizePlatformOps($request);

        $alert = $this->scopeToPlatformSchool(
            OperationsAlert::query()->whereNull('resolved_at'),
            $request,
        )->findOrFail($id);
        $alert->update(['resolved_at' => now()]);

        return response()->json(['data' => $alert, 'message' => 'Alert resolved']);
    }

    public function predictiveAnalytics(Request $request)
    {
        $this->authorizePlatformOps($request);

        $schoolId = $this->requirePlatformSchoolId($request);

        return response()->json([
            'data' => $this->predictive->studentRiskScores(
                $schoolId,
                $request->integer('limit') ?: 50,
            ),
        ]);
    }

    public function systemHealth(Request $request)
    {
        $this->authorizePlatformOps($request);

        return response()->json(['data' => $this->health->snapshot($this->platformSchoolId($request))]);
    }

    public function verifyAuditIntegrity(Request $request)
    {
        $this->authorizePlatformOps($request);

        $schoolId = $this->requirePlatformSchoolId($request);

        return response()->json([
            'data' => $this->auditIntegrity->verifyChain($schoolId),
        ]);
    }

    public function apiClients(Request $request)
    {
        $this->authorizePlatformOps($request);

        $rows = $this->scopeToPlatformSchool(ApiClient::query(), $request)
            ->with('school:id,name,code')
            ->select(['id', 'name', 'client_id', 'scopes', 'is_active', 'last_used_at', 'school_id', 'created_at'])
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function createApiClient(Request $request)
    {
        $this->authorizePlatformOps($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'name' => 'required|string',
            'scopes' => 'required|array',
        ])->validate();

        $result = $this->externalApi->createClient(
            $schoolId,
            $data['name'],
            $data['scopes'],
        );

        return response()->json([
            'data' => $result['client'],
            'client_secret' => $result['client_secret'],
            'message' => 'API client created — store the secret securely',
        ], 201);
    }
}
