<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Teacher;
use Illuminate\Support\Carbon;

class TeacherLeaveStatusService
{
    /**
     * Sync teacher status from approved leave covering the given day.
     *
     * @return array{marked_on_leave: int, restored_active: int}
     */
    public function sync(?int $schoolId = null, ?Carbon $on = null): array
    {
        $on = ($on ?? now())->copy()->startOfDay();

        $coveringTeacherIds = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $on)
            ->whereDate('end_date', '>=', $on)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('teacher_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $marked = Teacher::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->whereIn('id', $coveringTeacherIds ?: [0])
            ->update(['status' => 'on_leave']);

        $restored = Teacher::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'on_leave')
            ->whereNotIn('id', $coveringTeacherIds ?: [0])
            ->update(['status' => 'active']);

        return [
            'marked_on_leave' => $marked,
            'restored_active' => $restored,
        ];
    }

    public function syncTeacher(int $teacherId): void
    {
        $teacher = Teacher::query()->find($teacherId);
        if (! $teacher || $teacher->status === 'inactive') {
            return;
        }

        $today = now()->startOfDay();
        $covering = LeaveRequest::query()
            ->where('teacher_id', $teacherId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();

        if ($covering && $teacher->status !== 'on_leave') {
            $teacher->update(['status' => 'on_leave']);
        } elseif (! $covering && $teacher->status === 'on_leave') {
            $teacher->update(['status' => 'active']);
        }
    }
}
