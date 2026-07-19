<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
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
    public function __construct(
        private OperationsDashboardService $operations,
        private PredictiveAnalyticsService $predictive,
        private SystemHealthService $health,
        private AuditIntegrityService $auditIntegrity,
        private ExternalApiService $externalApi,
    ) {}

    public function liveDashboard(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->role === UserRole::SuperAdmin ? null : $user->school_id;

        return response()->json(['data' => $this->operations->liveFeed($schoolId)]);
    }

    public function resolveAlert(Request $request, int $id)
    {
        $user = $request->user();
        $query = OperationsAlert::query()->whereNull('resolved_at');

        if ($user->role !== UserRole::SuperAdmin) {
            $query->where('school_id', $user->school_id);
        }

        $alert = $query->findOrFail($id);
        $alert->update(['resolved_at' => now()]);

        return response()->json(['data' => $alert, 'message' => 'Alert resolved']);
    }

    public function predictiveAnalytics(Request $request)
    {
        return response()->json([
            'data' => $this->predictive->studentRiskScores(
                $request->user()->school_id,
                $request->integer('limit') ?: 50,
            ),
        ]);
    }

    public function systemHealth(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->role === UserRole::SuperAdmin ? null : $user->school_id;

        return response()->json(['data' => $this->health->snapshot($schoolId)]);
    }

    public function verifyAuditIntegrity(Request $request)
    {
        return response()->json([
            'data' => $this->auditIntegrity->verifyChain($request->user()->school_id),
        ]);
    }

    public function apiClients(Request $request)
    {
        return response()->json([
            'data' => ApiClient::where('school_id', $request->user()->school_id)
                ->select(['id', 'name', 'client_id', 'scopes', 'is_active', 'last_used_at', 'created_at'])
                ->get(),
        ]);
    }

    public function createApiClient(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'scopes' => 'required|array',
        ])->validate();

        $result = $this->externalApi->createClient(
            $request->user()->school_id,
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
