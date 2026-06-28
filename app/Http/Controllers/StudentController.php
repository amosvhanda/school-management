<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Setting;
use App\Models\Grade;
use App\Models\Exam;
use App\Models\StudentDocument;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function __construct(private StudentPromotionService $promotionService) {}
    public function index(Request $request)
    {
        $query = Student::query();

        // Filters
        if ($request->has('class')) {
            $query->where('class', $request->class);
        }
        if ($request->has('class_id')) {
            $classId = $request->class_id;
            if (is_numeric($classId)) {
                $class = \App\Models\ClassModel::find($classId);
                if ($class) {
                    $query->where(function ($q) use ($class) {
                        $q->where('class_id', $class->id)
                          ->orWhere('class', $class->name);
                    });
                } else {
                    $query->where('class_id', $classId);
                }
            }
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Support 'all=true' parameter to get all students without pagination
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            $students = $query->orderBy('full_name')->get();
        } else {
            // Support limit and sorting
            $limit = $request->get('limit', 50);
            $sort = $request->get('sort', 'created_at');
            $order = $request->get('order', 'desc');
            $students = $query->orderBy($sort, $order)->limit($limit)->get();
        }

        return response()->json([
            'data' => $students,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $query = Student::query();
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($id);

        return response()->json([
            'data' => $student,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'class' => 'required|string',
            'dateOfBirth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:255',
            'school' => 'nullable|string|max:255',
            'guardian.firstName' => 'nullable|string|max:255',
            'guardian.surname' => 'nullable|string|max:255',
            'guardian.phone' => 'nullable|string|max:20',
            'guardian.email' => 'nullable|email',
            'guardian.relationship' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $guardian = $request->input('guardian', []);
        
        // Ensure school_id is set (BelongsToSchool trait should handle this, but explicit is better)
        $schoolId = $user?->school_id;
        if (! $schoolId && $user && ! $user->isSuperAdmin()) {
            return response()->json([
                'message' => 'User must be associated with a school to create students',
                'errors' => ['school_id' => ['User is not associated with a school']],
            ], 422);
        }
        
        $student = Student::create([
            'first_name' => $request->firstName,
            'last_name' => $request->surname,
            'full_name' => $request->firstName . ' ' . $request->surname,
            'student_number' => 'SCH' . date('Y') . str_pad(
                Student::withoutGlobalScopes()->where('school_id', $schoolId)->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'class' => $request->class,
            'date_of_birth' => $request->dateOfBirth,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'suburb' => $request->suburb,
            'school' => $request->school ?? 'Mufakose 1 High School',
            'status' => 'active',
            'school_id' => $schoolId,
            'guardian_first_name' => $guardian['firstName'] ?? $guardian['first_name'] ?? null,
            'guardian_last_name' => $guardian['surname'] ?? $guardian['last_name'] ?? null,
            'guardian_phone' => $guardian['phone'] ?? null,
            'guardian_email' => $guardian['email'] ?? null,
            'guardian_relationship' => $guardian['relationship'] ?? null,
        ]);

        return response()->json([
            'data' => $student,
            'message' => 'Student created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $query = Student::query();
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'firstName' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'class' => 'sometimes|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:255',
            'guardian.firstName' => 'nullable|string|max:255',
            'guardian.surname' => 'nullable|string|max:255',
            'guardian.phone' => 'nullable|string|max:20',
            'guardian.email' => 'nullable|email',
            'guardian.relationship' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('firstName') || $request->has('surname')) {
            $student->first_name = $request->firstName ?? $student->first_name;
            $student->last_name = $request->surname ?? $student->last_name;
            $student->full_name = $student->first_name . ' ' . $student->last_name;
        }

        $student->fill($request->only(['class', 'phone', 'email', 'address', 'suburb']));
        $guardian = $request->input('guardian', []);
        if (!empty($guardian)) {
            $student->guardian_first_name = $guardian['firstName'] ?? $guardian['first_name'] ?? $student->guardian_first_name;
            $student->guardian_last_name = $guardian['surname'] ?? $guardian['last_name'] ?? $student->guardian_last_name;
            $student->guardian_phone = $guardian['phone'] ?? $student->guardian_phone;
            $student->guardian_email = $guardian['email'] ?? $student->guardian_email;
            $student->guardian_relationship = $guardian['relationship'] ?? $student->guardian_relationship;
        }
        $student->save();

        return response()->json([
            'data' => $student,
            'message' => 'Student updated successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $query = Student::query();
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($id);
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully',
        ]);
    }

    public function performance(Request $request, $student)
    {
        $user = $request->user();
        $query = Student::with(['grades', 'attendance']);
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($student);

        // Calculate performance metrics
        $grades = $student->grades;
        $averageScore = $grades->avg('score');
        $totalGrades = $grades->count();

        return response()->json([
            'data' => [
                'student' => $student,
                'grades' => $grades,
                'attendance' => $student->attendance,
                'average_score' => $averageScore,
                'total_grades' => $totalGrades,
            ],
        ]);
    }

    public function invoices(Request $request, $student)
    {
        $user = $request->user();
        $query = Student::with('invoices');
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($student);

        return response()->json([
            'data' => $student->invoices,
        ]);
    }

    public function uploadDocuments(Request $request, $student)
    {
        $user = $request->user();
        $query = Student::query();

        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $student = $query->findOrFail($student);

        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1',
            'documents.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'type' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = $student->school_id;
        $uploaded = [];

        foreach ($request->file('documents') as $file) {
            $path = $file->store("schools/{$schoolId}/students/{$student->id}/documents", 'public');

            $uploaded[] = StudentDocument::create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'uploaded_by' => $user?->id,
                'name' => $file->getClientOriginalName(),
                'type' => $request->input('type'),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return response()->json([
            'message' => count($uploaded).' document(s) uploaded successfully',
            'data' => $uploaded,
        ], 201);
    }

    public function exams(Request $request, $student)
    {
        $user = $request->user();
        $query = Student::query();
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($student);

        // Get exams for the student's grade level
        $exams = Exam::where('school_id', $student->school_id)
            ->where('grade_level_id', $student->grade_level_id)
            ->with(['term', 'gradeLevel', 'subject', 'examResults' => function($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->orderBy('exam_date', 'desc')
            ->get();

        // Format exam data with results
        $formattedExams = $exams->map(function($exam) {
            $result = $exam->examResults->first();
            return [
                'id' => $exam->id,
                'name' => $exam->name,
                'description' => $exam->description,
                'subject' => $exam->subject->name ?? null,
                'exam_date' => $exam->exam_date,
                'start_time' => $exam->start_time,
                'end_time' => $exam->end_time,
                'total_marks' => $exam->total_marks,
                'passing_marks' => $exam->passing_marks,
                'term' => $exam->term->name ?? null,
                'academic_year' => $exam->academic_year,
                'is_published' => $exam->is_published,
                'result' => $result ? [
                    'marks_obtained' => $result->marks_obtained,
                    'percentage' => $result->percentage,
                    'grade' => $result->grade,
                    'remarks' => $result->remarks,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $formattedExams,
        ]);
    }

    public function createInvoice(Request $request, $student)
    {
        $user = $request->user();
        $query = Student::query();
        
        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }
        
        $student = $query->findOrFail($student);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'dueDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = $user?->school_id ?? $student->school_id;
        $currency = $student->school?->currency ?? 'USD';
        if (! in_array($currency, ['USD', 'ZWG'], true)) {
            $currency = 'USD';
        }

        // Create invoice using Invoice model
        $invoice = \App\Models\Invoice::create([
            'student_id' => $student->id,
            'school_id' => $schoolId,
            'invoice_number' => 'INV-' . date('Y') . '-' . str_pad(\App\Models\Invoice::where('school_id', $schoolId)->count() + 1, 5, '0', STR_PAD_LEFT),
            'amount' => $request->amount,
            'amount_paid' => 0,
            'balance' => $request->amount,
            'description' => $request->description,
            'due_date' => $request->dueDate,
            'status' => 'pending',
            'currency' => $currency,
        ]);

        return response()->json([
            'data' => $invoice,
            'message' => 'Invoice created successfully',
        ], 201);
    }

    public function promote(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $academicYear = $request->input('academic_year', Setting::get('academic.academicYear', (string) now()->year));
        $nextAcademicYear = $request->input('next_academic_year', (string) ((int) $academicYear + 1));
        $passMark = (float) $request->input('pass_mark', 50);

        $result = $this->promotionService->runYearEnd(
            schoolId: $schoolId,
            academicYear: $academicYear,
            nextAcademicYear: $nextAcademicYear,
            passMark: $passMark,
            studentIds: null,
            processedBy: $request->user()->id,
        );

        return response()->json([
            'data' => $result,
            'message' => "Year-end complete: {$result['promoted']} promoted, {$result['repeated']} repeating, {$result['graduated']} graduated",
        ]);
    }

    public function bulkInvoices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'studentIds' => 'required|array',
            'description' => 'required|string',
            'dueDate' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $schoolId = $user?->school_id;
        $currency = $user?->school?->currency ?? 'USD';
        if (! in_array($currency, ['USD', 'ZWG'], true)) {
            $currency = 'USD';
        }
        
        $created = 0;
        foreach ($request->studentIds as $studentId) {
            $student = Student::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->find($studentId);
            if ($student) {
                $studentSchoolId = $schoolId ?? $student->school_id;
                \App\Models\Invoice::create([
                    'student_id' => $student->id,
                    'school_id' => $studentSchoolId,
                    'invoice_number' => 'INV-' . date('Y') . '-' . str_pad(\App\Models\Invoice::where('school_id', $studentSchoolId)->count() + 1, 5, '0', STR_PAD_LEFT),
                    'amount' => $request->amount,
                    'amount_paid' => 0,
                    'balance' => $request->amount,
                    'description' => $request->description,
                    'due_date' => $request->dueDate,
                    'status' => 'pending',
                    'currency' => $currency,
                ]);
                $created++;
            }
        }

        return response()->json([
            'data' => ['created' => $created],
            'message' => "Invoices created for {$created} students",
        ], 201);
    }

    public function bulkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'studentIds' => 'required|array',
            'status' => 'required|string|in:active,inactive,suspended,graduated',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = Student::whereIn('id', $request->studentIds)
            ->update(['status' => $request->status]);

        return response()->json([
            'data' => ['updated' => $updated],
            'message' => "Status updated for {$updated} students",
        ]);
    }

    public function bulkPromote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'studentIds' => 'required|array',
            'academic_year' => 'nullable|string',
            'next_academic_year' => 'nullable|string',
            'pass_mark' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $academicYear = $request->input('academic_year', Setting::get('academic.academicYear', (string) now()->year));
        $nextAcademicYear = $request->input('next_academic_year', (string) ((int) $academicYear + 1));

        $result = $this->promotionService->runYearEnd(
            schoolId: $request->user()->school_id,
            academicYear: $academicYear,
            nextAcademicYear: $nextAcademicYear,
            passMark: (float) $request->input('pass_mark', 50),
            studentIds: $request->studentIds,
            processedBy: $request->user()->id,
        );

        return response()->json([
            'data' => $result,
            'message' => "Promotion completed: {$result['promoted']} promoted, {$result['repeated']} repeated, {$result['graduated']} graduated",
        ]);
    }
}
