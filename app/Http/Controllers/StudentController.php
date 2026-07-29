<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\Domain\SchoolDomainRules;
use App\Services\FinancialLedgerService;
use App\Services\ParentAccessService;
use App\Services\StudentAdmissionService;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function __construct(
        private StudentPromotionService $promotionService,
        private ParentAccessService $parentAccess,
        private SchoolDomainRules $domainRules,
        private StudentAdmissionService $admissionService,
        private FinancialLedgerService $ledgerService,
    ) {}

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
                $class = ClassModel::find($classId);
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
            $query->where(function ($q) use ($search) {
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

        $studentNumber = $this->admissionService->generateStudentNumber((int) $schoolId);
        $this->domainRules->assertAdmissionNumberAvailable((int) $schoolId, $studentNumber);

        $student = Student::create([
            'first_name' => $request->firstName,
            'last_name' => $request->surname,
            'full_name' => $request->firstName.' '.$request->surname,
            'student_number' => $studentNumber,
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
            $student->full_name = $student->first_name.' '.$student->last_name;
        }

        $student->fill($request->only(['class', 'phone', 'email', 'address', 'suburb']));
        $guardian = $request->input('guardian', []);
        if (! empty($guardian)) {
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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['students.manage'],
        );
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
        $student = $this->parentAccess->assertCanAccessStudent($request->user(), (int) $student);
        $viewerIsLearner = in_array($request->user()->role, [UserRole::Student, UserRole::Parent], true);

        $examsQuery = Exam::where('school_id', $student->school_id)
            ->where('grade_level_id', $student->grade_level_id)
            ->with(['term', 'gradeLevel', 'subject', 'examResults' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->orderBy('exam_date', 'desc');

        if ($viewerIsLearner) {
            $examsQuery->where(function ($q) {
                $q->where('is_published', true)
                    ->orWhereNotNull('results_approved_at');
            });
        }

        $formattedExams = $examsQuery->get()->map(function ($exam) use ($viewerIsLearner) {
            $result = $exam->examResults->first();
            $showResult = $result && (! $viewerIsLearner || $exam->is_published || $exam->results_approved_at);

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
                'is_published' => (bool) $exam->is_published,
                'result' => $showResult ? [
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

    /**
     * Download an individual student's results as CSV or printable HTML report card.
     */
    public function downloadResults(Request $request, $student): StreamedResponse|Response
    {
        $student = $this->parentAccess->assertCanAccessStudent($request->user(), (int) $student);
        $format = strtolower((string) $request->query('format', 'html'));
        if (! in_array($format, ['html', 'csv'], true)) {
            $format = 'html';
        }

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderBy('term')
            ->orderBy('subject')
            ->get();

        $examResults = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('is_published', true)
                        ->orWhereNotNull('results_approved_at');
                });
            })
            ->with(['exam:id,name,exam_date,total_marks,academic_year,term_id', 'exam.term:id,name', 'subject:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $this->domainRules->assertReportCardHasGrades($student, $grades, $examResults);

        $school = School::query()->find($student->school_id);
        $schoolName = $school?->name ?? 'School';
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($student->full_name ?: 'student')) ?: 'student';
        $generatedAt = now()->timezone('Africa/Harare')->format('d M Y, H:i');

        if ($format === 'csv') {
            $filename = "{$safeName}_results_{$student->student_number}.csv";

            return response()->streamDownload(function () use ($grades, $examResults, $student, $schoolName, $generatedAt) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['School', $schoolName]);
                fputcsv($out, ['Student', $student->full_name]);
                fputcsv($out, ['Student number', $student->student_number]);
                fputcsv($out, ['Class', $student->class]);
                fputcsv($out, ['Generated', $generatedAt]);
                fputcsv($out, []);
                fputcsv($out, ['Report card grades']);
                fputcsv($out, ['Subject', 'Score', 'Total', 'Grade', 'Term', 'Year']);
                foreach ($grades as $grade) {
                    fputcsv($out, [
                        $grade->subject,
                        $grade->score,
                        $grade->total,
                        $grade->grade,
                        $grade->term,
                        $grade->year,
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Exam results']);
                fputcsv($out, ['Exam', 'Subject', 'Exam date', 'Marks', 'Total', 'Percentage', 'Grade', 'Term', 'Year']);
                foreach ($examResults as $result) {
                    $examDate = $result->exam?->exam_date;
                    fputcsv($out, [
                        $result->exam?->name,
                        $result->subject?->name,
                        $examDate ? Carbon::parse($examDate)->format('d M Y') : '',
                        $result->marks_obtained,
                        $result->total_marks,
                        $result->percentage,
                        $result->grade,
                        $result->exam?->term?->name,
                        $result->exam?->academic_year,
                    ]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $html = $this->buildReportCardHtml($schoolName, $student, $grades, $examResults, $generatedAt);
        $filename = "{$safeName}_report_card.html";

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @param  Collection<int, Grade>  $grades
     * @param  Collection<int, ExamResult>  $examResults
     */
    private function buildReportCardHtml(
        string $schoolName,
        Student $student,
        $grades,
        $examResults,
        string $generatedAt,
    ): string {
        $gradeRows = $grades->map(function (Grade $grade) {
            return '<tr>'
                .'<td>'.e((string) ($grade->subject ?? '—')).'</td>'
                .'<td>'.e((string) ($grade->score ?? '—')).'</td>'
                .'<td>'.e((string) ($grade->total ?? '—')).'</td>'
                .'<td>'.e((string) ($grade->grade ?? '—')).'</td>'
                .'<td>'.e((string) ($grade->term ?? '—')).'</td>'
                .'<td>'.e((string) ($grade->year ?? '—')).'</td>'
                .'</tr>';
        })->implode('');

        if ($gradeRows === '') {
            $gradeRows = '<tr><td colspan="6">No report-card grades recorded yet.</td></tr>';
        }

        $examRows = $examResults->map(function (ExamResult $result) {
            $examDate = $result->exam?->exam_date
                ? Carbon::parse($result->exam->exam_date)->format('d M Y')
                : '—';

            return '<tr>'
                .'<td>'.e((string) ($result->exam?->name ?? '—')).'</td>'
                .'<td>'.e((string) ($result->subject?->name ?? '—')).'</td>'
                .'<td>'.e($examDate).'</td>'
                .'<td>'.e((string) ($result->marks_obtained ?? '—')).'</td>'
                .'<td>'.e((string) ($result->total_marks ?? '—')).'</td>'
                .'<td>'.e((string) ($result->percentage ?? '—')).'%</td>'
                .'<td>'.e((string) ($result->grade ?? '—')).'</td>'
                .'<td>'.e((string) ($result->exam?->term?->name ?? '—')).'</td>'
                .'</tr>';
        })->implode('');

        if ($examRows === '') {
            $examRows = '<tr><td colspan="8">No published exam results yet.</td></tr>';
        }

        $title = e((string) ($student->full_name ?? 'Student')).' — Report card';
        $school = e($schoolName);
        $fullName = e((string) ($student->full_name ?? '—'));
        $studentNumber = e((string) ($student->student_number ?? '—'));
        $className = e((string) ($student->class ?? '—'));
        $generated = e($generatedAt);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>{$title}</title>
  <style>
    body { font-family: Georgia, "Times New Roman", serif; color: #111; margin: 32px; }
    h1, h2 { font-family: Arial, Helvetica, sans-serif; margin: 0 0 8px; }
    .meta { color: #444; margin-bottom: 24px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin: 12px 0 28px; font-size: 14px; }
    th, td { border: 1px solid #bbb; padding: 8px 10px; text-align: left; }
    th { background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; }
    .footer { margin-top: 32px; font-size: 12px; color: #666; }
    @media print { body { margin: 16px; } }
  </style>
</head>
<body>
  <h1>{$school}</h1>
  <h2>Student report card</h2>
  <div class="meta">
    <div><strong>Student:</strong> {$fullName}</div>
    <div><strong>Student number:</strong> {$studentNumber}</div>
    <div><strong>Class:</strong> {$className}</div>
    <div><strong>Generated:</strong> {$generated}</div>
  </div>

  <h2>Continuous assessment / report grades</h2>
  <table>
    <thead>
      <tr>
        <th>Subject</th>
        <th>Score</th>
        <th>Total</th>
        <th>Grade</th>
        <th>Term</th>
        <th>Year</th>
      </tr>
    </thead>
    <tbody>{$gradeRows}</tbody>
  </table>

  <h2>Examination results</h2>
  <table>
    <thead>
      <tr>
        <th>Exam</th>
        <th>Subject</th>
        <th>Date</th>
        <th>Marks</th>
        <th>Total</th>
        <th>%</th>
        <th>Grade</th>
        <th>Term</th>
      </tr>
    </thead>
    <tbody>{$examRows}</tbody>
  </table>

  <p class="footer">This document was generated from the school management system. Open and use Print → Save as PDF if needed.</p>
</body>
</html>
HTML;
    }

    public function createInvoice(Request $request, $student)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );
        $user = $request->user();
        $query = Student::query();

        // Ensure school isolation (unless super_admin)
        if ($user && ! $user->isSuperAdmin() && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $student = $query->findOrFail($student);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'dueDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice = $this->ledgerService->createInvoice(
            student: $student,
            amount: (float) $request->amount,
            description: (string) $request->description,
            dueDate: new \DateTimeImmutable((string) $request->dueDate),
            createdBy: $user?->id,
        );

        return response()->json([
            'data' => $invoice,
            'message' => 'Invoice created successfully',
        ], 201);
    }

    public function promote(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['students.manage'],
        );
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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );
        $validator = Validator::make($request->all(), [
            'studentIds' => 'required|array',
            'description' => 'required|string',
            'dueDate' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $schoolId = $user?->school_id;
        $dueDate = new \DateTimeImmutable((string) $request->dueDate);
        $created = 0;

        foreach ($request->studentIds as $studentId) {
            $student = Student::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->find($studentId);
            if ($student) {
                $this->ledgerService->createInvoice(
                    student: $student,
                    amount: (float) $request->amount,
                    description: (string) $request->description,
                    dueDate: $dueDate,
                    createdBy: $user?->id,
                );
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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['students.manage'],
        );
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
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['students.manage'],
        );
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
