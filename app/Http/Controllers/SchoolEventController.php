<?php

namespace App\Http\Controllers;

use App\Models\SchoolEvent;
use Illuminate\Http\Request;

class SchoolEventController extends Controller
{
    private function authorizeEventManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['communications.manage', 'academics.manage'],
        );
    }

    public function index(Request $request)
    {
        $query = SchoolEvent::where('school_id', $request->user()->school_id);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->orderBy('starts_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEventManage($request);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $event = SchoolEvent::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $event], 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeEventManage($request);

        $event = SchoolEvent::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'nullable|string|max:100',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:scheduled,ongoing,completed,cancelled',
        ]);

        $event->update($data);

        return response()->json(['data' => $event->fresh(), 'message' => 'Event updated']);
    }
}
