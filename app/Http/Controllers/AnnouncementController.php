<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\ParentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    public function __construct(private ParentNotificationService $parentNotifications) {}

    /**
     * List announcements for staff management (includes hidden/inactive).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;
        $limit = min((int) $request->get('limit', 200), 500);

        $query = Announcement::query()
            ->with('creator:id,name,first_name,last_name')
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('target_audience') && $request->target_audience !== 'all') {
            $audience = $request->target_audience;
            $query->where(function ($q) use ($audience) {
                $q->where('target_audience', 'all')
                    ->orWhere('target_audience', $audience);
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('message', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->limit($limit)->get(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $announcement = Announcement::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with('creator:id,name,first_name,last_name')
            ->findOrFail($id);

        return response()->json(['data' => $announcement]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:info,important,warning,success',
            'target_audience' => 'required|string|in:all,students,parents,teachers,staff',
            'date' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if ($user->school_id === null) {
            return response()->json(['message' => 'User must belong to a school to create announcements.'], 403);
        }

        $announcement = Announcement::create([
            ...$request->only(['title', 'message', 'type', 'target_audience', 'date']),
            'created_by' => $user->id,
            'school_id' => $user->school_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($announcement->is_active) {
            $this->parentNotifications->notifyAnnouncement($announcement);
        }

        return response()->json([
            'message' => 'Announcement created successfully',
            'data' => $announcement->load('creator:id,name,first_name,last_name'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $announcement = Announcement::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'type' => 'sometimes|required|string|in:info,important,warning,success',
            'target_audience' => 'sometimes|required|string|in:all,students,parents,teachers,staff',
            'date' => 'sometimes|required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $wasActive = (bool) $announcement->is_active;

        $announcement->update($request->only([
            'title',
            'message',
            'type',
            'target_audience',
            'date',
            'is_active',
        ]));

        $announcement->refresh();

        if (! $wasActive && $announcement->is_active) {
            $this->parentNotifications->notifyAnnouncement($announcement);
        }

        return response()->json([
            'message' => 'Announcement updated successfully',
            'data' => $announcement->load('creator:id,name,first_name,last_name'),
        ]);
    }

    public function destroy($id)
    {
        $user = request()->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $announcement = Announcement::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $announcement->delete();

        return response()->json([
            'message' => 'Announcement deleted successfully',
        ]);
    }
}
