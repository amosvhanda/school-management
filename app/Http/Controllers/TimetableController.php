<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ClassModel;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\TimetableConflictService;
use App\Services\TimetableGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableConflictService $conflictService,
        protected TimetableGeneratorService $generatorService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->school_id;
        $query = Timetable::with(['classModel', 'teacher', 'subject', 'room'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $scope = $this->applyRoleScope($query, $user);

        // Admins may further filter; students/teachers stay locked to their scope.
        if ($scope === 'school') {
            if ($request->filled('class_id')) {
                $classId = $request->class_id;
                if (is_numeric($classId)) {
                    $query->where('class_id', (int) $classId);
                } else {
                    $class = ClassModel::query()
                        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                        ->where('name', $classId)
                        ->first();
                    if ($class) {
                        $query->where('class_id', $class->id);
                    }
                }
            }

            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->teacher_id);
            }
        }

        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }

        $timetable = $query->orderBy('day')->orderBy('start_time')->get();

        return response()->json([
            'data' => $timetable,
            'meta' => [
                'scope' => $scope,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if ($forbidden = $this->forbidUnlessCanManage($request)) {
            return $forbidden;
        }

        $schoolId = $request->user()?->school_id;

        $validator = Validator::make($request->all(), [
            'class_id' => [
                'required',
                Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject_id' => [
                'required_without:subject',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject' => 'required_without:subject_id|string',
            'day' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'room_id' => [
                'nullable',
                Rule::exists('rooms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'room' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subjectName = $request->subject;
        $subjectId = $request->subject_id;
        if ($subjectId) {
            $subject = Subject::where('school_id', $schoolId)->findOrFail($subjectId);
            $subjectName = $subject->name;
        }

        $roomName = $request->room;
        if ($request->room_id) {
            $room = Room::where('school_id', $schoolId)->findOrFail($request->room_id);
            $roomName = $room->name;
        }

        $timetableData = array_merge($request->all(), [
            'school_id' => $schoolId,
            'subject' => $subjectName,
            'subject_id' => $subjectId,
            'room' => $roomName,
        ]);
        $validation = $this->conflictService->validateTimetableEntry($timetableData);

        if (! $validation['valid']) {
            return response()->json([
                'message' => 'Timetable conflicts detected',
                'conflicts' => $validation['conflicts'],
            ], 422);
        }

        $timetable = Timetable::create($timetableData);

        return response()->json([
            'data' => $timetable->load(['classModel', 'teacher', 'subject', 'room']),
            'message' => 'Timetable slot created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if ($forbidden = $this->forbidUnlessCanManage($request)) {
            return $forbidden;
        }

        $timetable = Timetable::findOrFail($id);

        $schoolId = $request->user()?->school_id;

        $validator = Validator::make($request->all(), [
            'subject_id' => [
                'sometimes',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'subject' => 'sometimes|string',
            'day' => 'sometimes|string',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'room_id' => [
                'nullable',
                Rule::exists('rooms', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'room' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $subjectName = $request->input('subject', $timetable->subject);
        $subjectId = $request->input('subject_id', $timetable->subject_id);

        if ($request->filled('subject_id')) {
            $subject = Subject::where('school_id', $schoolId)->findOrFail($request->subject_id);
            $subjectName = $subject->name;
        }

        $roomName = $request->input('room', $timetable->room);
        if ($request->filled('room_id')) {
            $room = Room::where('school_id', $schoolId)->findOrFail($request->room_id);
            $roomName = $room->name;
        }

        $timetableData = [
            'class_id' => $timetable->class_id,
            'day' => $request->input('day', $timetable->day),
            'start_time' => $request->input('start_time', $timetable->start_time instanceof \DateTimeInterface
                ? $timetable->start_time->format('H:i')
                : $timetable->start_time),
            'end_time' => $request->input('end_time', $timetable->end_time instanceof \DateTimeInterface
                ? $timetable->end_time->format('H:i')
                : $timetable->end_time),
            'teacher_id' => $request->has('teacher_id') ? $request->teacher_id : $timetable->teacher_id,
            'room_id' => $request->has('room_id') ? $request->room_id : $timetable->room_id,
            'school_id' => $schoolId ?? $timetable->school_id,
            'subject' => $subjectName,
            'subject_id' => $subjectId,
            'room' => $roomName,
        ];

        $validation = $this->conflictService->validateTimetableEntry($timetableData, $timetable->id);

        if (! $validation['valid']) {
            return response()->json([
                'message' => 'Timetable conflicts detected',
                'conflicts' => $validation['conflicts'],
            ], 422);
        }

        $timetable->fill($timetableData);
        $timetable->save();

        return response()->json([
            'data' => $timetable->load(['classModel', 'teacher', 'subject', 'room']),
            'message' => 'Timetable slot updated successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if ($forbidden = $this->forbidUnlessCanManage($request)) {
            return $forbidden;
        }

        $timetable = Timetable::findOrFail($id);
        $timetable->delete();

        return response()->json([
            'message' => 'Timetable slot deleted successfully',
        ]);
    }

    public function generate(Request $request)
    {
        if ($forbidden = $this->forbidUnlessCanManage($request)) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'days' => 'nullable|array|min:1',
            'days.*' => 'string',
            'day_start' => 'nullable|date_format:H:i',
            'period_minutes' => 'nullable|integer|min:30|max:120',
            'replace_existing' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $schoolId = $request->user()->school_id;

        $result = $this->generatorService->generateForClass(
            schoolId: $schoolId,
            classId: (int) $request->class_id,
            days: $request->input('days', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
            dayStart: $request->input('day_start', '07:30'),
            periodMinutes: (int) $request->input('period_minutes', 45),
            replaceExisting: $request->boolean('replace_existing'),
        );

        return response()->json([
            'message' => "Generated {$result['created']} timetable slot(s).",
            'data' => [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'conflicts' => $result['conflicts'],
                'entries' => Timetable::with(['classModel', 'teacher', 'subject', 'room'])
                    ->whereIn('id', $result['entries']->pluck('id'))
                    ->get(),
            ],
        ]);
    }

    public function generateBulk(Request $request)
    {
        if ($forbidden = $this->forbidUnlessCanManage($request)) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'class_ids' => 'nullable|array|min:1',
            'class_ids.*' => 'integer|exists:classes,id',
            'grade_level_id' => 'nullable|integer|exists:grade_levels,id',
            'all_classes' => 'nullable|boolean',
            'days' => 'nullable|array|min:1',
            'days.*' => 'string',
            'day_start' => 'nullable|date_format:H:i',
            'period_minutes' => 'nullable|integer|min:30|max:120',
            'replace_existing' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if (! $request->boolean('all_classes') && empty($request->input('class_ids'))) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'class_ids' => ['Provide class_ids or set all_classes to true.'],
                ],
            ], 422);
        }

        $schoolId = $request->user()->school_id;
        $classIds = $request->boolean('all_classes') ? null : $request->input('class_ids');

        $result = $this->generatorService->generateBulk(
            schoolId: $schoolId,
            classIds: $classIds,
            gradeLevelId: $request->input('grade_level_id'),
            days: $request->input('days', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
            dayStart: $request->input('day_start', '07:30'),
            periodMinutes: (int) $request->input('period_minutes', 45),
            replaceExisting: $request->boolean('replace_existing'),
        );

        return response()->json([
            'message' => "Bulk generation complete: {$result['created']} slot(s) across {$result['classes_processed']} class(es).",
            'data' => [
                'classes_processed' => $result['classes_processed'],
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'conflicts' => $result['conflicts'],
                'classes' => $result['classes'],
                'entries' => Timetable::with(['classModel', 'teacher', 'subject', 'room'])
                    ->whereIn('id', $result['entry_ids'])
                    ->get(),
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Timetable>  $query
     */
    private function applyRoleScope($query, ?User $user): string
    {
        if (! $user) {
            return 'none';
        }

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::tryFromMixed(is_string($user->role) ? $user->role : null);

        if ($role === UserRole::Student) {
            $student = Student::query()->where('user_id', $user->id)->first();
            if (! $student?->class_id) {
                $query->whereRaw('1 = 0');

                return 'student';
            }
            $query->where('class_id', $student->class_id);

            return 'student';
        }

        if ($role === UserRole::Teacher) {
            $teacherId = Teacher::query()->where('user_id', $user->id)->value('id');
            if (! $teacherId) {
                $query->whereRaw('1 = 0');

                return 'teacher';
            }
            $query->where('teacher_id', $teacherId);

            return 'teacher';
        }

        // Full school timetable is admin-only (parents/finance/etc. must not see all slots).
        if (app(PermissionService::class)->hasCapability($user, 'canManageTeachers')) {
            return 'school';
        }

        $query->whereRaw('1 = 0');

        return 'none';
    }

    private function forbidUnlessCanManage(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user || ! app(PermissionService::class)->hasCapability($user, 'canManageTeachers')) {
            return response()->json([
                'message' => 'Only school administrators can modify the timetable.',
            ], 403);
        }

        return null;
    }
}
