<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenanceLog;
use App\Services\WorkflowService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private WorkflowService $workflows) {}

    public function index(Request $request)
    {
        $query = Asset::where('school_id', $request->user()->school_id)->with('custodian:id,name');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_tag' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string',
            'custodian_user_id' => 'nullable|exists:users,id',
        ]);

        $asset = Asset::create([...$data, 'school_id' => $request->user()->school_id]);

        return response()->json(['data' => $asset], 201);
    }

    public function update(Request $request, int $id)
    {
        $asset = Asset::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'asset_tag' => 'nullable|string|max:100',
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string',
            'custodian_user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:active,maintenance,disposed',
        ]);

        $asset->update($data);

        return response()->json(['data' => $asset->fresh()->load('custodian:id,name'), 'message' => 'Asset updated']);
    }

    public function logMaintenance(Request $request, int $id)
    {
        $asset = Asset::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'maintenance_date' => 'required|date',
            'description' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
        ]);

        $log = $asset->maintenanceLogs()->create([...$data, 'performed_by' => $request->user()->id]);

        return response()->json(['data' => $log], 201);
    }

    public function dispose(Request $request, int $id)
    {
        $asset = Asset::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'disposed_at' => 'required|date',
            'reason' => 'required|string',
            'submit_for_approval' => 'nullable|boolean',
        ]);

        $instance = $this->workflows->start('asset_disposal', $asset, $request->user(), ['reason' => $data['reason']]);

        $disposal = AssetDisposal::create([
            'asset_id' => $asset->id,
            'disposed_at' => $data['disposed_at'],
            'reason' => $data['reason'],
            'workflow_instance_id' => $instance->id,
        ]);

        if (! $request->boolean('submit_for_approval', true)) {
            $asset->update(['status' => 'disposed']);
        }

        return response()->json(['data' => $disposal->load('asset')], 201);
    }
}
