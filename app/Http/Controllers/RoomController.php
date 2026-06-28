<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Get all rooms for the school
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        
        $rooms = Room::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return response()->json($rooms);
    }

    /**
     * Store a new room
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('rooms')->where('school_id', $schoolId)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('rooms')->where('school_id', $schoolId)],
            'type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $room = Room::create([
            'school_id' => $schoolId,
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($room, 201);
    }

    /**
     * Update a room
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $room = Room::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('rooms')->where('school_id', $schoolId)->ignore($id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('rooms')->where('school_id', $schoolId)->ignore($id)],
            'type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $room->update($validated);

        return response()->json($room);
    }

    /**
     * Delete a room
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $room = Room::where('school_id', $schoolId)->findOrFail($id);

        // Check if room is in use
        if ($room->classes()->exists() || $room->timetables()->exists()) {
            return response()->json([
                'message' => 'Cannot delete room that is in use'
            ], 422);
        }

        $room->delete();

        return response()->json(['message' => 'Room deleted successfully']);
    }
}
