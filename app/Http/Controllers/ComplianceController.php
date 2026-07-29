<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\Policy;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    private function authorizeCompliance(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['compliance.manage'],
        );
    }

    public function policies(Request $request)
    {
        $this->authorizeCompliance($request);

        $query = Policy::where('school_id', $request->user()->school_id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->orderByDesc('effective_date')->get()]);
    }

    public function storePolicy(Request $request)
    {
        $this->authorizeCompliance($request);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string',
            'content' => 'required|string',
            'effective_date' => 'nullable|date',
            'review_date' => 'nullable|date',
        ]);

        $policy = Policy::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $policy], 201);
    }

    public function updatePolicy(Request $request, int $id)
    {
        $this->authorizeCompliance($request);

        $policy = Policy::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'nullable|string',
            'content' => 'sometimes|string',
            'effective_date' => 'nullable|date',
            'review_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,archived,draft',
        ]);

        $policy->update($data);

        return response()->json(['data' => $policy->fresh(), 'message' => 'Policy updated']);
    }

    public function incidents(Request $request)
    {
        $this->authorizeCompliance($request);

        $query = IncidentReport::where('school_id', $request->user()->school_id)->with('reporter:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    public function storeIncident(Request $request)
    {
        $this->authorizeCompliance($request);

        $data = $request->validate([
            'category' => 'required|string|max:255',
            'severity' => 'nullable|string|in:low,medium,high,critical',
            'description' => 'required|string',
        ]);

        $incident = IncidentReport::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'reported_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $incident], 201);
    }

    public function updateIncident(Request $request, int $id)
    {
        $this->authorizeCompliance($request);

        $incident = IncidentReport::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'category' => 'sometimes|string|max:255',
            'severity' => 'nullable|string|in:low,medium,high,critical',
            'description' => 'sometimes|string',
            'status' => 'nullable|string|in:open,investigating,resolved,closed',
        ]);

        $incident->update($data);

        return response()->json(['data' => $incident->fresh()->load('reporter:id,name'), 'message' => 'Incident updated']);
    }
}
