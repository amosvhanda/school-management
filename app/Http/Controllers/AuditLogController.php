<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\LoginHistoryResource;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAuditAccess($request);

        $schoolId = $request->user()?->role === UserRole::SuperAdmin
            ? $request->input('school_id')
            : $request->user()?->school_id;

        $query = AuditLog::query()
            ->with(['user:id,name,email,role,first_name,last_name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }
        if ($request->filled('auditable_id')) {
            $query->where('auditable_id', $request->auditable_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50))
            ->through(fn (AuditLog $log) => (new AuditLogResource($log))->resolve());

        return response()->json($logs);
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeAuditAccess($request);

        $schoolId = $request->user()?->school_id;

        $log = AuditLog::query()
            ->with(['user:id,name,email,role'])
            ->when($schoolId && $request->user()?->role !== UserRole::SuperAdmin, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        return response()->json(['data' => (new AuditLogResource($log))->resolve()]);
    }

    public function loginHistory(Request $request)
    {
        $this->authorizeAuditAccess($request);

        $schoolId = $request->user()?->role === UserRole::SuperAdmin
            ? $request->input('school_id')
            : $request->user()?->school_id;

        $query = LoginHistory::query()
            ->with(['user:id,name,email,role'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $history = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50))
            ->through(fn (LoginHistory $row) => (new LoginHistoryResource($row))->resolve());

        return response()->json($history);
    }

    protected function authorizeAuditAccess(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canViewAuditLogs'],
            permissionSlugs: ['audit.view'],
        );
    }
}
