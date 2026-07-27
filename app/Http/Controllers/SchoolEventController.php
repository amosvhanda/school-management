<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Models\SchoolEvent;
use Illuminate\Http\Request;

class SchoolEventController extends Controller
{
    use RespondsWithPaginatedList;

    public function index(Request $request)
    {
        $query = SchoolEvent::where('school_id', $request->user()->school_id);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $this->indexResponse($request, $query->orderBy('starts_at'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:scheduled,ongoing,completed,cancelled',
        ]);

        $event = SchoolEvent::create([
            ...$data,
            'status' => $data['status'] ?? 'scheduled',
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $event], 201);
    }

    public function update(Request $request, int $id)
    {
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

    public function destroy(Request $request, int $id)
    {
        $event = SchoolEvent::where('school_id', $request->user()->school_id)->findOrFail($id);

        $event->delete();

        return response()->json(['message' => 'Event deleted']);
    }
}
