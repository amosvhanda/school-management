<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\Policy;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function policies(Request $request)
    {
        return response()->json(['data' => Policy::where('school_id', $request->user()->school_id)->orderByDesc('effective_date')->get()]);
    }

    public function storePolicy(Request $request)
    {
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

    public function incidents(Request $request)
    {
        return response()->json([
            'data' => IncidentReport::where('school_id', $request->user()->school_id)
                ->with('reporter:id,name')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function storeIncident(Request $request)
    {
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
}
