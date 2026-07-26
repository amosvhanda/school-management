<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Services\AttendanceNotificationService;
use App\Services\Domain\SchoolDomainRules;
use App\Services\PermissionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;

class AttendanceController extends Controller
{
    use HandlesResourceQueries;

    protected AttendanceNotificationService $notificationService;

    public function __construct(AttendanceNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['attendance.manage'],
        );

        $schoolId = $request->user()?->school_id;
        $user = $request->user();

        $base = Attendance::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        // Teachers only see attendance for classes they are assigned to (or class teacher of).
        if ($user?->isTeacher() && ! app(PermissionService::class)->hasCapability($user, 'canManageTeachers')) {
            $teacherId = $user->teacher?->id;
            if (! $teacherId) {
                return response()->json(['message' => 'Teacher account not linked to staff record', 'data' => []], 200);
            }

            $assignedClassIds = TeacherAssignment::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->pluck('class_id');

            $homeroomIds = ClassModel::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('teacher_id', $teacherId)
                ->pluck('id');

            $classIds = $assignedClassIds->merge($homeroomIds)->filter()->unique()->values();
            $base->whereIn('class_id', $classIds->isEmpty() ? [0] : $classIds->all());
        }

        return $this->paginateResource($request, Attendance::class, AttendanceResource::class, [
            'base' => $base,
            'filters' => [
                AllowedFilter::callback('class_id', function ($query, $value) use ($schoolId) {
                    if (! is_numeric($value)) {
                        $class = ClassModel::query()
                            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                            ->where('name', $value)
                            ->first();
                        if ($class) {
                            $query->where('class_id', $class->id);

                            return;
                        }
                    }
                    $query->where('class_id', $value);
                }),
                'date',
                'student_id',
                'status',
                'search',
            ],
            'search_columns' => ['student.full_name', 'student.student_number'],
            'sorts' => ['date', 'created_at', 'status'],
            'includes' => ['student', 'classModel'],
            'default_sort' => '-date',
            'with' => ['student', 'classModel'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['attendance.manage'],
        );

        $validator = Validator::make($request->all(), [
            'class_id' => 'required', // Can be string (class name) or integer (class ID)
            'date' => 'required|date',
            'overwrite' => 'nullable|boolean',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|string|in:present,absent,late,excused,sick,left_early',
            'records.*.remarks' => 'nullable|string',
            'records.*.time' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $schoolId = $user?->school_id;

        // Handle class_id - can be class name or class ID
        $classId = $request->class_id;
        $class = null;
        if (! is_numeric($classId)) {
            // If it's a class name, try to find the class ID
            $class = ClassModel::where('name', $classId)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->first();
            if ($class) {
                $classId = $class->id;
            } else {
                // If class not found, use null (will be stored as string in class_id field)
                $classId = $request->class_id;
            }
        }
        if ($classId && is_numeric($classId)) {
            $class = ClassModel::where('id', $classId)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->first();
        }

        // Enforce class teacher assignment for teachers
        if ($user?->isTeacher()) {
            $teacherId = $user->teacher?->id;
            if (! $teacherId) {
                return response()->json([
                    'message' => 'Teacher account not linked to staff record',
                ], 403);
            }

            $isClassTeacher = $class && (int) $class->teacher_id === (int) $teacherId;
            $hasAssignment = TeacherAssignment::where('school_id', $schoolId)
                ->where('teacher_id', $teacherId)
                ->where('class_id', $classId)
                ->exists();

            if (! $isClassTeacher && ! $hasAssignment) {
                return response()->json([
                    'message' => 'Only the assigned class teacher can mark attendance for this class.',
                ], 403);
            }
        }

        $attendanceRecords = [];
        $normalizedDate = $request->date('date')->toDateString();
        $normalizedClassId = is_numeric($classId) ? (int) $classId : null;
        $allowOverwrite = $request->boolean('overwrite', true);
        $domainRules = app(SchoolDomainRules::class);
        $studentIds = collect($request->records)->pluck('student_id')->map(fn ($id) => (int) $id)->all();
        $studentsById = Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        if ($studentsById->count() !== count($studentIds)) {
            return response()->json([
                'message' => 'One or more students could not be found for this school.',
                'errors' => ['student_id' => ['Invalid student selected for attendance.']],
            ], 422);
        }

        $skippedInactive = [];
        $recordsToSave = [];

        foreach ($request->records as $record) {
            $student = $studentsById->get((int) $record['student_id']);
            $status = strtolower((string) ($student->status ?? 'active'));

            if (in_array($status, SchoolDomainRules::inactiveStudentStatuses(), true)) {
                $name = trim((string) ($student->full_name ?: $student->student_number)) ?: ('#'.$student->id);
                $skippedInactive[] = "{$name} ({$status})";

                continue;
            }

            $recordsToSave[] = $record;
        }

        if ($recordsToSave === []) {
            throw DomainException::make(
                'student_inactive',
                'No active learners to save on this register.',
                ['student' => $skippedInactive !== []
                    ? ['Inactive learners were skipped: '.implode(', ', $skippedInactive).'.']
                    : ['All selected learners have inactive accounts.']],
            );
        }

        foreach ($recordsToSave as $record) {
            $student = $studentsById->get((int) $record['student_id']);
            $domainRules->assertStudentActive($student);
            if ($class) {
                if ($student->class_id && (int) $student->class_id !== (int) $class->id) {
                    return response()->json([
                        'message' => 'Student does not belong to selected class',
                        'errors' => ['student_id' => ['Student class mismatch']],
                    ], 422);
                }
                if (! $student->class_id && $student->class && $student->class !== $class->name) {
                    return response()->json([
                        'message' => 'Student does not belong to selected class',
                        'errors' => ['student_id' => ['Student class mismatch']],
                    ], 422);
                }
            }

            if (! $allowOverwrite) {
                $existing = $this->findAttendanceForUpsert(
                    (int) $schoolId,
                    (int) $record['student_id'],
                    $normalizedDate,
                    $normalizedClassId,
                );
                if ($existing) {
                    throw DomainException::make(
                        'attendance_already_recorded',
                        'Attendance already recorded for the day.',
                        ['date' => ['Attendance for this student is already recorded for '.$normalizedDate.'.']],
                    );
                }
            }

            $attendance = $this->upsertAttendanceRecord(
                schoolId: (int) $schoolId,
                studentId: (int) $record['student_id'],
                date: $normalizedDate,
                classId: $normalizedClassId,
                values: [
                    'status' => $record['status'],
                    'remarks' => $record['remarks'] ?? null,
                    'time_in' => $record['time'] ?? null,
                    'marked_by' => $user->id,
                    'teacher_id' => $request->teacher_id ?? $user->teacher?->id,
                    'subject_id' => $record['subject_id'] ?? null,
                    'lesson_type' => $record['lesson_type'] ?? null,
                ],
            );

            // Send notification if student is absent
            if ($attendance->status === 'absent') {
                $this->notificationService->notifyAbsence($attendance);
            }

            $attendanceRecords[] = $attendance;
        }

        return response()->json([
            'data' => $attendanceRecords,
            'message' => $skippedInactive === []
                ? 'Attendance recorded successfully'
                : 'Attendance saved for active learners. Skipped inactive: '.implode(', ', $skippedInactive).'.',
            'skipped_inactive' => $skippedInactive,
        ], 201);
    }

    /**
     * Upsert attendance without tripping the unique index when legacy rows
     * (null class_id / school_id) exist or the school global scope hides a match.
     */
    private function upsertAttendanceRecord(
        int $schoolId,
        int $studentId,
        string $date,
        ?int $classId,
        array $values,
    ): Attendance {
        $payload = array_merge($values, [
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'date' => $date,
            'class_id' => $classId,
        ]);

        return DB::transaction(function () use ($schoolId, $studentId, $date, $classId, $payload) {
            $existing = $this->findAttendanceForUpsert($schoolId, $studentId, $date, $classId, lock: true);

            if ($existing) {
                $existing->update($payload);

                return $existing->fresh();
            }

            try {
                return Attendance::withoutGlobalScopes()->create($payload);
            } catch (UniqueConstraintViolationException $e) {
                $retry = $this->findAttendanceForUpsert($schoolId, $studentId, $date, $classId, lock: true);
                if ($retry) {
                    $retry->update($payload);

                    return $retry->fresh();
                }

                throw $e;
            }
        });
    }

    private function findAttendanceForUpsert(
        int $schoolId,
        int $studentId,
        string $date,
        ?int $classId,
        bool $lock = false,
    ): ?Attendance {
        $query = Attendance::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->whereDate('date', $date);

        if ($schoolId) {
            $query->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhereNull('school_id');
            });
        }

        if ($classId !== null) {
            $query->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)->orWhereNull('class_id');
            });
        } else {
            $query->whereNull('class_id');
        }

        $query->orderByRaw('CASE WHEN class_id IS NULL THEN 1 ELSE 0 END');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function studentSummary(Request $request, $id)
    {
        $schoolId = $request->user()?->school_id;
        $attendance = Attendance::where('student_id', $id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();

        $summary = [
            'total_days' => $attendance->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'excused' => $attendance->where('status', 'excused')->count(),
            'attendance_rate' => $attendance->count() > 0
                ? ($attendance->where('status', 'present')->count() / $attendance->count()) * 100
                : 0,
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function classReport(Request $request, $id)
    {
        // Handle both class name and class ID
        $classId = $id;
        $schoolId = $request->user()?->school_id;
        if (! is_numeric($classId)) {
            $class = ClassModel::where('name', $classId)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->first();
            if ($class) {
                $classId = $class->id;
            }
        }

        $attendance = Attendance::with('student')
            ->where('class_id', $classId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();

        $summary = [
            'total_students' => $attendance->pluck('student_id')->unique()->count(),
            'total_records' => $attendance->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'excused' => $attendance->where('status', 'excused')->count(),
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function todaySummary(Request $request)
    {
        $today = now()->toDateString();
        $schoolId = $request->user()?->school_id;

        $attendance = Attendance::whereDate('date', $today)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();

        $present = $attendance->where('status', 'present')->count();
        $absent = $attendance->where('status', 'absent')->count();
        $late = $attendance->where('status', 'late')->count();
        $excused = $attendance->where('status', 'excused')->count();
        $total = $present + $absent + $late + $excused;

        // Frontend dashboard expects counts (present, absent, late), not percentages.
        $totalStudents = Student::where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->count();
        $actualTotal = $totalStudents > 0 ? $totalStudents : max($total, 1);

        return response()->json([
            'data' => [
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'total' => $actualTotal,
                'date' => $today,
            ],
        ]);
    }
}
