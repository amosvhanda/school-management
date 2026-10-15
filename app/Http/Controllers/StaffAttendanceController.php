<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaffAttendanceController extends Controller
{
    /**
     * @return list<string>
     */
    protected function manageCapabilities(): array
    {
        return ['canManageTeachers'];
    }

    /**
     * @return list<string>
     */
    protected function managePermissionSlugs(): array
    {
        return ['hr.manage'];
    }

    protected function authorizeManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: $this->manageCapabilities(),
            permissionSlugs: $this->managePermissionSlugs(),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = (int) $request->user()->school_id;
        $date = $request->input('date', now()->toDateString());

        $validator = Validator::make(
            ['date' => $date, 'staff_type' => $request->input('staff_type')],
            [
                'date' => ['required', 'date'],
                'staff_type' => ['nullable', 'in:teacher,employee'],
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = StaffAttendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->with('recorder:id,name');

        if ($request->filled('staff_type')) {
            $query->where('staff_type', $request->string('staff_type')->toString());
        }

        $attendances = $query->orderBy('staff_type')->orderBy('staff_id')->get();

        return response()->json([
            'data' => $attendances,
            'meta' => ['date' => $date],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = (int) $request->user()->school_id;

        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.staff_type' => ['required', 'in:teacher,employee'],
            'entries.*.staff_id' => ['required', 'integer'],
            'entries.*.status' => ['required', 'in:present,absent,late,half_day,on_leave'],
            'entries.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $validator->validated()['date'];
        $entries = $validator->validated()['entries'];
        $recordedBy = $request->user()->id;
        $saved = [];

        foreach ($entries as $entry) {
            $this->assertStaffBelongsToSchool(
                $schoolId,
                $entry['staff_type'],
                (int) $entry['staff_id'],
            );

            $attendance = StaffAttendance::query()->updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'date' => $date,
                    'staff_type' => $entry['staff_type'],
                    'staff_id' => $entry['staff_id'],
                ],
                [
                    'status' => $entry['status'],
                    'remarks' => $entry['remarks'] ?? null,
                    'recorded_by' => $recordedBy,
                ],
            );

            $saved[] = $attendance;
        }

        return response()->json([
            'message' => 'Staff attendance saved successfully',
            'data' => $saved,
        ], 201);
    }

    public function roster(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = (int) $request->user()->school_id;
        $date = $request->input('date', now()->toDateString());

        $validator = Validator::make(['date' => $date], [
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $existing = StaffAttendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy(fn (StaffAttendance $row) => $row->staff_type.':'.$row->staff_id);

        $teachers = Teacher::query()
            ->where('school_id', $schoolId)
            ->where(function (Builder $q) {
                $q->whereNull('status')->orWhereIn('status', ['active', 'on_leave']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id', 'email', 'department', 'status'])
            ->map(function (Teacher $teacher) use ($existing) {
                $attendance = $existing->get('teacher:'.$teacher->id);
                $defaultStatus = $teacher->status === 'on_leave' ? 'on_leave' : null;

                return [
                    'staff_type' => 'teacher',
                    'staff_id' => $teacher->id,
                    'name' => $teacher->name,
                    'employee_number' => $teacher->employee_id,
                    'email' => $teacher->email,
                    'department' => $teacher->department,
                    'employment_status' => $teacher->status,
                    'status' => $attendance?->status ?? $defaultStatus,
                    'remarks' => $attendance?->remarks,
                    'attendance_id' => $attendance?->id,
                ];
            });

        $employees = Employee::query()
            ->where('school_id', $schoolId)
            ->where(function ($q) {
                $q->whereNull('status')->orWhereIn('status', ['active', 'on_leave']);
            })
            ->with('designation:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number', 'email', 'designation_id', 'status'])
            ->map(function (Employee $employee) use ($existing) {
                $attendance = $existing->get('employee:'.$employee->id);
                $defaultStatus = $employee->status === 'on_leave' ? 'on_leave' : null;

                return [
                    'staff_type' => 'employee',
                    'staff_id' => $employee->id,
                    'name' => $employee->name,
                    'employee_number' => $employee->employee_number,
                    'email' => $employee->email,
                    'designation' => $employee->designation?->name,
                    'employment_status' => $employee->status,
                    'status' => $attendance?->status ?? $defaultStatus,
                    'remarks' => $attendance?->remarks,
                    'attendance_id' => $attendance?->id,
                ];
            });

        return response()->json([
            'data' => [
                'teachers' => $teachers,
                'employees' => $employees,
            ],
            'meta' => ['date' => $date],
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $schoolId = (int) $request->user()->school_id;
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $validator = Validator::make(
            ['year' => $year, 'month' => $month, 'staff_type' => $request->input('staff_type')],
            [
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'min:1', 'max:12'],
                'staff_type' => ['nullable', 'in:teacher,employee'],
            ],
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = StaffAttendance::query()
            ->where('school_id', $schoolId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($request->filled('staff_type')) {
            $query->where('staff_type', $request->string('staff_type')->toString());
        }

        $rows = $query->orderBy('date')->get();

        $byStatus = $rows->groupBy('status')->map->count();
        $byStaffType = $rows->groupBy('staff_type')->map->count();

        $staffKeys = $rows->map(fn (StaffAttendance $r) => $r->staff_type.':'.$r->staff_id)->unique();
        $teacherIds = $rows->where('staff_type', 'teacher')->pluck('staff_id')->unique()->values();
        $employeeIds = $rows->where('staff_type', 'employee')->pluck('staff_id')->unique()->values();

        $teacherNames = Teacher::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $teacherIds)
            ->pluck('name', 'id');
        $employeeNames = Employee::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $employeeIds)
            ->pluck('name', 'id');

        $perStaff = $staffKeys->map(function (string $key) use ($rows, $teacherNames, $employeeNames) {
            [$type, $id] = explode(':', $key, 2);
            $staffRows = $rows->where('staff_type', $type)->where('staff_id', (int) $id);
            $name = $type === 'teacher'
                ? ($teacherNames[(int) $id] ?? "Teacher #{$id}")
                : ($employeeNames[(int) $id] ?? "Employee #{$id}");

            return [
                'staff_type' => $type,
                'staff_id' => (int) $id,
                'name' => $name,
                'present' => $staffRows->where('status', 'present')->count(),
                'absent' => $staffRows->where('status', 'absent')->count(),
                'late' => $staffRows->where('status', 'late')->count(),
                'half_day' => $staffRows->where('status', 'half_day')->count(),
                'on_leave' => $staffRows->where('status', 'on_leave')->count(),
                'marked_days' => $staffRows->count(),
            ];
        })->sortBy('name')->values();

        return response()->json([
            'data' => [
                'by_status' => $byStatus,
                'by_staff_type' => $byStaffType,
                'staff' => $perStaff,
                'total_marks' => $rows->count(),
            ],
            'meta' => [
                'year' => $year,
                'month' => $month,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    protected function assertStaffBelongsToSchool(int $schoolId, string $staffType, int $staffId): void
    {
        $exists = match ($staffType) {
            'teacher' => Teacher::query()->where('school_id', $schoolId)->whereKey($staffId)->exists(),
            'employee' => Employee::query()->where('school_id', $schoolId)->whereKey($staffId)->exists(),
            default => false,
        };

        abort_unless($exists, 422, "Invalid {$staffType} id for this school.");
    }
}
