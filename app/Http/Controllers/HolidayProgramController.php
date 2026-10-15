<?php

namespace App\Http\Controllers;

use App\Models\HolidayAttendance;
use App\Models\HolidayEnrollment;
use App\Models\HolidayProgram;
use App\Models\Student;
use App\Services\HolidayProgramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HolidayProgramController extends Controller
{
    public function __construct(private HolidayProgramService $holidayService) {}

    private function authorizeHolidayPrograms(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['operations.manage'],
        );
    }


    public function index(Request $request)
    {
        $this->authorizeHolidayPrograms($request);

        $programs = HolidayProgram::where('school_id', $request->user()->school_id)
            ->orderByDesc('start_date')
            ->get();

        return response()->json(['data' => $programs]);
    }

    public function store(Request $request)
    {
        $this->authorizeHolidayPrograms($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year' => 'nullable|string|max:9',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'fee_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $program = HolidayProgram::create([
            'school_id' => $request->user()->school_id,
            ...$data,
            'currency' => $data['currency'] ?? ($request->user()->school?->getDefaultCurrency() ?? 'USD'),
            'fee_amount' => $data['fee_amount'] ?? 0,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return response()->json(['data' => $program, 'message' => 'Holiday program created'], 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeHolidayPrograms($request);

        $program = HolidayProgram::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'fee_amount' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        // Evaluate cross-date validity manually during partial updates
        $startDate = $data['start_date'] ?? $program->start_date;
        $endDate = $data['end_date'] ?? $program->end_date;

        if (strtotime($endDate) < strtotime($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be a date after or equal to start date.'
            ]);
        }

        $program->update($data);

        return response()->json(['data' => $program]);
    }

    public function enrollments(Request $request, int $id)
    {
        $this->authorizeHolidayPrograms($request);

        $program = HolidayProgram::where('school_id', $request->user()->school_id)->findOrFail($id);
        $enrollments = HolidayEnrollment::where('holiday_program_id', $program->id)
            ->with(['student', 'invoice'])
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    public function enroll(Request $request, int $id)
    {
        $this->authorizeHolidayPrograms($request);

        $schoolId = $request->user()->school_id;
        $program = HolidayProgram::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate(['student_id' => 'required|integer']);

        // Explicitly seal the cross-tenant boundary for the student ID
        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        // Ensure student isn't already enrolled to prevent double billing/records
        $alreadyEnrolled = HolidayEnrollment::where('holiday_program_id', $program->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($alreadyEnrolled) {
            throw ValidationException::withMessages(['student_id' => 'This student is already enrolled in this program.']);
        }

        $enrollment = $this->holidayService->enrollStudent($program, $student->id, $request->user()->id);

        return response()->json([
            'data' => $enrollment->load(['student', 'invoice']),
            'message' => 'Student enrolled in holiday program and invoiced if applicable',
        ], 201);
    }

    public function recordAttendance(Request $request, int $id)
    {
        $this->authorizeHolidayPrograms($request);

        $schoolId = $request->user()->school_id;
        $program = HolidayProgram::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'student_id' => 'required|integer',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|string',
        ]);

        // Secure student domain context
        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);

        // Guard date parameters to prevent junk data entries outside of scope
        $attendanceDate = Carbon::parse($data['date']);
        $programStart = Carbon::parse($program->start_date)->startOfDay();
        $programEnd = Carbon::parse($program->end_date)->endOfDay();

        if ($attendanceDate->lt($programStart) || $attendanceDate->gt($programEnd)) {
            throw ValidationException::withMessages([
                'date' => "The attendance date must fall within the program schedule ({$program->start_date} to {$program->end_date})."
            ]);
        }

        $enrolled = HolidayEnrollment::where('holiday_program_id', $program->id)
            ->where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->exists();

        if (! $enrolled) {
            throw ValidationException::withMessages([
                'student_id' => 'Student is not actively enrolled in this holiday program.'
            ]);
        }

        $record = HolidayAttendance::updateOrCreate(
            [
                'holiday_program_id' => $program->id,
                'student_id' => $student->id,
                'date' => $data['date'],
            ],
            [
                'school_id' => $schoolId,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ],
        );

        return response()->json(['data' => $record]);
    }

    public function attendance(Request $request, int $id)
    {
        $this->authorizeHolidayPrograms($request);

        $program = HolidayProgram::where('school_id', $request->user()->school_id)->findOrFail($id);
        $records = HolidayAttendance::where('holiday_program_id', $program->id)
            ->with('student')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $records]);
    }
}
