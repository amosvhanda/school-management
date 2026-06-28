<?php

namespace App\Services;

use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TimetableConflictService
{
    /**
     * Check for conflicts when creating/updating a timetable entry
     */
    public function checkConflicts(array $timetableData, ?int $excludeId = null): array
    {
        $conflicts = [
            'teacher' => [],
            'room' => [],
            'class' => [],
        ];

        $day = $timetableData['day'] ?? null;
        $startTimeRaw = $timetableData['start_time'] ?? null;
        $endTimeRaw = $timetableData['end_time'] ?? null;

        if (! $day || ! $startTimeRaw || ! $endTimeRaw) {
            return $conflicts;
        }

        $startTime = Carbon::parse($startTimeRaw);
        $endTime = Carbon::parse($endTimeRaw);

        if ($endTime->lte($startTime)) {
            return $conflicts;
        }

        $teacherId = $timetableData['teacher_id'] ?? null;
        $roomId = $timetableData['room_id'] ?? null;
        $classId = $timetableData['class_id'] ?? null;
        $schoolId = $timetableData['school_id'] ?? Auth::user()?->school_id;

        // Check teacher conflicts
        if ($teacherId) {
            $teacherConflicts = Timetable::where('school_id', $schoolId)
                ->where('day', $day)
                ->where('teacher_id', $teacherId)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        // New entry starts during existing entry
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>', $startTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        // New entry ends during existing entry
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>=', $endTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        // New entry completely contains existing entry
                        $q->where('start_time', '>=', $startTime)
                          ->where('end_time', '<=', $endTime);
                    });
                })
                ->when($excludeId, function ($query) use ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                })
                ->with(['classModel', 'subject', 'teacher'])
                ->get();

            if ($teacherConflicts->isNotEmpty()) {
                $conflicts['teacher'] = $teacherConflicts->map(function ($conflict) {
                    return [
                        'id' => $conflict->id,
                        'class' => $conflict->classModel?->name,
                        'subject' => $conflict->subject?->name ?? $conflict->subject,
                        'time' => Carbon::parse($conflict->start_time)->format('H:i') . ' - ' . Carbon::parse($conflict->end_time)->format('H:i'),
                    ];
                })->toArray();
            }
        }

        // Check room conflicts
        if ($roomId) {
            $roomConflicts = Timetable::where('school_id', $schoolId)
                ->where('day', $day)
                ->where('room_id', $roomId)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>', $startTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>=', $endTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '>=', $startTime)
                          ->where('end_time', '<=', $endTime);
                    });
                })
                ->when($excludeId, function ($query) use ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                })
                ->with(['classModel', 'subject', 'room'])
                ->get();

            if ($roomConflicts->isNotEmpty()) {
                $conflicts['room'] = $roomConflicts->map(function ($conflict) {
                    return [
                        'id' => $conflict->id,
                        'class' => $conflict->classModel?->name,
                        'subject' => $conflict->subject?->name ?? $conflict->subject,
                        'time' => Carbon::parse($conflict->start_time)->format('H:i') . ' - ' . Carbon::parse($conflict->end_time)->format('H:i'),
                    ];
                })->toArray();
            }
        }

        if ($classId) {
            $classConflicts = Timetable::where('school_id', $schoolId)
                ->where('day', $day)
                ->where('class_id', $classId)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>', $startTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                            ->where('end_time', '>=', $endTime);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '>=', $startTime)
                            ->where('end_time', '<=', $endTime);
                    });
                })
                ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
                ->with(['classModel', 'subject', 'teacher'])
                ->get();

            if ($classConflicts->isNotEmpty()) {
                $conflicts['class'] = $classConflicts->map(function ($conflict) {
                    return [
                        'id' => $conflict->id,
                        'class' => $conflict->classModel?->name,
                        'subject' => $conflict->subject?->name ?? $conflict->subject,
                        'time' => Carbon::parse($conflict->start_time)->format('H:i').' - '.Carbon::parse($conflict->end_time)->format('H:i'),
                    ];
                })->toArray();
            }
        }

        return $conflicts;
    }

    /**
     * Validate timetable entry and return conflicts if any
     */
    public function validateTimetableEntry(array $timetableData, ?int $excludeId = null): array
    {
        $conflicts = $this->checkConflicts($timetableData, $excludeId);
        $hasConflicts = ! empty($conflicts['teacher']) || ! empty($conflicts['room']) || ! empty($conflicts['class']);

        return [
            'valid' => !$hasConflicts,
            'conflicts' => $conflicts,
        ];
    }
}
