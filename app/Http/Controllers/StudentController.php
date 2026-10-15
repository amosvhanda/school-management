<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\Domain\SchoolDomainRules;
use App\Services\FinancialLedgerService;
use App\Services\ParentAccessService;
use App\Services\FileUploadService;
use App\Services\StudentAdmissionService;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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

    /**
     * Get the authenticated user's tenant/school ID.
     *
     * Super administrators are blocked from school-scoped student operations.
     */
    protected function getSchoolId(Request $request): int
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->isSuperAdmin()) {
            abort(403, 'Super administrators cannot access school-scoped student operations.');
        }

        if (! $user->school_id) {
            abort(403, 'User is not associated with a school.');
        }

        return (int) $user->school_id;
    }

    /**
     * Base tenant-safe student query.
     */
    protected function studentQuery(Request $request)
    {
        return Student::query()
            ->where('school_id', $this->getSchoolId($request));
    }

    /**
     * Find a student within the authenticated tenant.
     */
    protected function findSchoolStudent(Request $request, $student): Student
    {
        return $this->studentQuery($request)
            ->findOrFail((int) $student);
    }

    /**
     * Safely validate a route-model-bound student.
     */
    protected function resolveTenantStudent(
        Request $request,
        Student $student
    ): Student {
        if ((int) $student->school_id !== $this->getSchoolId($request)) {
            abort(404);
        }

        return $student;
    }

    /**
     * Check whether the current user is a student or parent.
     */
    protected function isPortalUser(Request $request): bool
    {
        $user = $request->user();

        return $user &&
            in_array(
                $user->role,
                [
                    UserRole::Student,
                    UserRole::Parent,
                ],
                true
            );
    }

    public function index(Request $request)
    {
        $query = $this->studentQuery($request);

        if ($request->filled('class')) {
            $query->where(
                'class',
                $request->input('class')
            );
        }

        if ($request->filled('class_id')) {
            $classId = $request->input('class_id');

            if (is_numeric($classId)) {
                $classQuery = ClassModel::query();

                $schoolId = $this->getSchoolId($request);

                if ($schoolId !== null) {
                    $classQuery->where(
                        'school_id',
                        $schoolId
                    );
                }

                $class = $classQuery->find(
                    (int) $classId
                );

                if ($class) {
                    $query->where(function ($q) use ($class) {
                        $q->where(
                            'class_id',
                            $class->id
                        )->orWhere(
                            'class',
                            $class->name
                        );
                    });
                } else {
                    $query->where(
                        'class_id',
                        (int) $classId
                    );
                }
            }
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'full_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'first_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'last_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'student_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    );
                });
            }
        }

        /*
         * Do not allow arbitrary column names from the request.
         */
        $allowedSorts = [
            'id',
            'full_name',
            'first_name',
            'last_name',
            'student_number',
            'class',
            'status',
            'created_at',
            'updated_at',
        ];

        $sort = $request->input(
            'sort',
            'created_at'
        );

        if (! in_array(
            $sort,
            $allowedSorts,
            true
        )) {
            $sort = 'created_at';
        }

        $order = strtolower(
            (string) $request->input(
                'order',
                'desc'
            )
        );

        if (! in_array(
            $order,
            ['asc', 'desc'],
            true
        )) {
            $order = 'desc';
        }

        if (
            $request->get('all') === 'true' ||
            $request->get('all') === true ||
            $request->boolean('all')
        ) {
            $students = $query
                ->orderBy(
                    $sort,
                    $order
                )
                ->get();
        } else {
            $limit = (int) $request->get(
                'limit',
                50
            );

            $limit = max(
                1,
                min($limit, 200)
            );

            $students = $query
                ->orderBy(
                    $sort,
                    $order
                )
                ->limit($limit)
                ->get();
        }

        return response()->json([
            'data' => $students,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        /*
         * Student and parent users must pass relationship-based
         * access control.
         */
        if (
            $user &&
            in_array(
                $user->role,
                [
                    UserRole::Student,
                    UserRole::Parent,
                ],
                true
            )
        ) {
            $student = $this->parentAccess
                ->assertCanAccessStudent(
                    $user,
                    (int) $id
                );
        } else {
            $student = $this->findSchoolStudent(
                $request,
                $id
            );
        }

        return response()->json([
            'data' => $student,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'firstName' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'class' => 'required|string|max:255',
                'dateOfBirth' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'suburb' => 'nullable|string|max:255',

                /*
                 * This is retained for compatibility with your
                 * existing frontend, but normal users cannot use
                 * it to change the actual tenant.
                 */
                'school' => 'nullable|string|max:255',

                /*
                 * Only relevant when a super admin explicitly
                 * creates a student for a tenant.
                 */
                'school_id' => [
                    'nullable',
                    'integer',
                    'exists:schools,id',
                ],

                'guardian' => 'nullable|array',
                'guardian.firstName' =>
                    'nullable|string|max:255',
                'guardian.surname' =>
                    'nullable|string|max:255',
                'guardian.phone' =>
                    'nullable|string|max:20',
                'guardian.email' =>
                    'nullable|email|max:255',
                'guardian.relationship' =>
                    'nullable|string|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $authenticatedSchoolId =
            $this->getSchoolId($request);

        /*
         * Normal users ALWAYS use their authenticated school.
         */
        if ($authenticatedSchoolId !== null) {
            $schoolId = $authenticatedSchoolId;
        } else {
            /*
             * Super admin must explicitly select a school.
             */
            $schoolId = $request->input(
                'school_id'
            );

            if (! $schoolId) {
                return response()->json([
                    'message' =>
                        'school_id is required when a super admin creates a student.',
                    'errors' => [
                        'school_id' => [
                            'Please select the school for this student.',
                        ],
                    ],
                ], 422);
            }

            $schoolId = (int) $schoolId;
        }

        $guardian = $request->input(
            'guardian',
            []
        );

        $studentNumber =
            $this->admissionService
                ->generateStudentNumber(
                    (int) $schoolId
                );

        $this->domainRules
            ->assertAdmissionNumberAvailable(
                (int) $schoolId,
                $studentNumber
            );

        $schoolModel = School::query()
            ->findOrFail($schoolId);

        $student = Student::create([
            'first_name' =>
                $request->input('firstName'),

            'last_name' =>
                $request->input('surname'),

            'full_name' =>
                trim(
                    $request->input('firstName').
                    ' '.
                    $request->input('surname')
                ),

            'student_number' =>
                $studentNumber,

            'class' =>
                $request->input('class'),

            'date_of_birth' =>
                $request->input('dateOfBirth'),

            'gender' =>
                $request->input('gender'),

            'phone' =>
                $request->input('phone'),

            'email' =>
                $request->input('email'),

            'address' =>
                $request->input('address'),

            'suburb' =>
                $request->input('suburb'),

            /*
             * For normal users, the school name comes from
             * the authenticated tenant.
             */
            'school' =>
                $schoolModel->name,

            'status' =>
                'active',

            /*
             * This is the critical tenant field.
             */
            'school_id' =>
                $schoolId,

            'guardian_first_name' =>
                $guardian['firstName']
                ?? $guardian['first_name']
                ?? null,

            'guardian_last_name' =>
                $guardian['surname']
                ?? $guardian['last_name']
                ?? null,

            'guardian_phone' =>
                $guardian['phone']
                ?? null,

            'guardian_email' =>
                $guardian['email']
                ?? null,

            'guardian_relationship' =>
                $guardian['relationship']
                ?? null,
        ]);

        return response()->json([
            'data' => $student,
            'message' =>
                'Student created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $student = $this->findSchoolStudent(
            $request,
            $id
        );

        $validator = Validator::make(
            $request->all(),
            [
                'firstName' =>
                    'sometimes|string|max:255',

                'surname' =>
                    'sometimes|string|max:255',

                'class' =>
                    'sometimes|string|max:255',

                'phone' =>
                    'nullable|string|max:20',

                'email' =>
                    'nullable|email|max:255',

                'address' =>
                    'nullable|string',

                'suburb' =>
                    'nullable|string|max:255',

                'guardian' =>
                    'nullable|array',

                'guardian.firstName' =>
                    'nullable|string|max:255',

                'guardian.surname' =>
                    'nullable|string|max:255',

                'guardian.phone' =>
                    'nullable|string|max:20',

                'guardian.email' =>
                    'nullable|email|max:255',

                'guardian.relationship' =>
                    'nullable|string|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        if (
            $request->has('firstName') ||
            $request->has('surname')
        ) {
            $student->first_name =
                $request->input(
                    'firstName',
                    $student->first_name
                );

            $student->last_name =
                $request->input(
                    'surname',
                    $student->last_name
                );

            $student->full_name =
                trim(
                    $student->first_name.
                    ' '.
                    $student->last_name
                );
        }

        $student->fill(
            $request->only([
                'class',
                'phone',
                'email',
                'address',
                'suburb',
            ])
        );

        $guardian = $request->input(
            'guardian',
            []
        );

        if (! empty($guardian)) {
            $student->guardian_first_name =
                $guardian['firstName']
                ?? $guardian['first_name']
                ?? $student->guardian_first_name;

            $student->guardian_last_name =
                $guardian['surname']
                ?? $guardian['last_name']
                ?? $student->guardian_last_name;

            $student->guardian_phone =
                $guardian['phone']
                ?? $student->guardian_phone;

            $student->guardian_email =
                $guardian['email']
                ?? $student->guardian_email;

            $student->guardian_relationship =
                $guardian['relationship']
                ?? $student->guardian_relationship;
        }

        /*
         * Never allow update requests to change school_id.
         */
        $student->school_id =
            $student->school_id;

        $student->save();

        return response()->json([
            'data' => $student,
            'message' =>
                'Student updated successfully',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $student = $this->findSchoolStudent(
            $request,
            $id
        );

        $student->delete();

        return response()->json([
            'message' =>
                'Student deleted successfully',
        ]);
    }

    public function performance(
        Request $request,
        $student
    ) {
        $user = $request->user();

        if (
            $user &&
            in_array(
                $user->role,
                [
                    UserRole::Student,
                    UserRole::Parent,
                ],
                true
            )
        ) {
            $studentModel =
                $this->parentAccess
                    ->assertCanAccessStudent(
                        $user,
                        (int) $student
                    );
        } else {
            $studentModel =
                $this->findSchoolStudent(
                    $request,
                    $student
                );
        }

        $studentModel->load([
            'grades',
            'attendance',
        ]);

        $grades =
            $studentModel->grades;

        $averageScore =
            $grades->avg('score');

        $totalGrades =
            $grades->count();

        return response()->json([
            'data' => [
                'student' =>
                    $studentModel,

                'grades' =>
                    $grades,

                'attendance' =>
                    $studentModel->attendance,

                'average_score' =>
                    $averageScore,

                'total_grades' =>
                    $totalGrades,
            ],
        ]);
    }

    public function invoices(
        Request $request,
        $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $user = $request->user();

        if (
            $user &&
            in_array(
                $user->role,
                [
                    UserRole::Student,
                    UserRole::Parent,
                ],
                true
            )
        ) {
            $studentModel =
                $this->parentAccess
                    ->assertCanAccessStudent(
                        $user,
                        (int) $student
                    );
        } else {
            $studentModel =
                $this->findSchoolStudent(
                    $request,
                    $student
                );
        }

        $studentModel->load(
            'invoices'
        );

        return response()->json([
            'data' =>
                $studentModel->invoices,
        ]);
    }

    public function documents(
        Request $request,
        $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $studentModel =
            $this->findSchoolStudent(
                $request,
                $student
            );

        $docs =
            $studentModel
                ->documents()
                ->where(
                    'school_id',
                    $studentModel->school_id
                )
                ->with(
                    'uploader:id,name'
                )
                ->orderByDesc('id')
                ->get()
                ->map(
                    fn (
                        StudentDocument $doc
                    ) =>
                        $this->formatStudentDocument(
                            $doc
                        )
                );

        return response()->json([
            'data' => $docs,
        ]);
    }

    public function uploadDocuments(
        Request $request,
        $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $user = $request->user();

        $studentModel =
            $this->findSchoolStudent(
                $request,
                $student
            );

        $validator = Validator::make(
            $request->all(),
            [
                'documents' =>
                    'required|array|min:1',

                'documents.*' =>
                    'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',

                'type' =>
                    'nullable|string|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        $schoolId =
            (int) $studentModel->school_id;

        $uploaded = [];

        $uploads =
            app(FileUploadService::class);

        foreach (
            $request->file('documents')
            as $file
        ) {
            $stored =
                $uploads->store(
                    $file,
                    "students/{$studentModel->id}/documents",
                    'public',
                    $schoolId,
                );

            $document =
                StudentDocument::create([
                    'school_id' =>
                        $schoolId,

                    'student_id' =>
                        $studentModel->id,

                    'uploaded_by' =>
                        $user?->id,

                    'name' =>
                        $stored['name'],

                    'type' =>
                        $request->input('type'),

                    'path' =>
                        $stored['path'],

                    'mime_type' =>
                        $stored['mime']
                        ?? $file->getMimeType(),

                    'size' =>
                        $stored['size'],
                ]);

            $uploaded[] =
                $this->formatStudentDocument(
                    $document
                );
        }

        return response()->json([
            'message' =>
                count($uploaded).
                ' document(s) uploaded successfully',

            'data' =>
                $uploaded,
        ], 201);
    }

    public function destroyDocument(
        Request $request,
        $student,
        StudentDocument $document
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $studentModel =
            $this->findSchoolStudent(
                $request,
                $student
            );

        /*
         * Verify document belongs to BOTH:
         *
         * 1. This student
         * 2. This student's school
         */
        if (
            (int) $document->student_id !==
                (int) $studentModel->id
        ) {
            abort(404);
        }

        if (
            (int) $document->school_id !==
                (int) $studentModel->school_id
        ) {
            abort(404);
        }

        if ($document->path) {
            Storage::disk('public')
                ->delete(
                    $document->path
                );
        }

        $document->delete();

        return response()->json([
            'message' =>
                'Document deleted',
        ]);
    }

    protected function formatStudentDocument(
        StudentDocument $doc
    ): array {
        return [
            'id' =>
                $doc->id,

            'name' =>
                $doc->name,

            'type' =>
                $doc->type,

            'mime_type' =>
                $doc->mime_type,

            'size' =>
                $doc->size,

            'url' =>
                $doc->path
                    ? Storage::disk('public')
                        ->url($doc->path)
                    : null,

            'uploaded_by' =>
                $doc->uploader
                    ? [
                        'id' =>
                            $doc->uploader->id,

                        'name' =>
                            $doc->uploader->name,
                    ]
                    : null,

            'created_at' =>
                $doc->created_at
                    ?->toIso8601String(),
        ];
    }

    public function exams(
        Request $request,
        $student
    ) {
        $studentModel =
            $this->parentAccess
                ->assertCanAccessStudent(
                    $request->user(),
                    (int) $student
                );

        $viewerIsLearner =
            in_array(
                $request->user()->role,
                [
                    UserRole::Student,
                    UserRole::Parent,
                ],
                true
            );

        $examsQuery =
            Exam::where(
                'school_id',
                $studentModel->school_id
            )
            ->where(
                'grade_level_id',
                $studentModel->grade_level_id
            )
            ->with([
                'term',
                'gradeLevel',
                'subject',
                'examResults' => function ($q) use (
                    $studentModel
                ) {
                    $q->where(
                        'student_id',
                        $studentModel->id
                    );
                },
            ])
            ->orderBy(
                'exam_date',
                'desc'
            );

        if ($viewerIsLearner) {
            $examsQuery->where(
                function ($q) {
                    $q->where(
                        'is_published',
                        true
                    )
                    ->orWhereNotNull(
                        'results_approved_at'
                    );
                }
            );
        }

        $formattedExams =
            $examsQuery
                ->get()
                ->map(
                    function ($exam) use (
                        $viewerIsLearner
                    ) {
                        $result =
                            $exam->examResults
                                ->first();

                        $showResult =
                            $result &&
                            (
                                ! $viewerIsLearner ||
                                $exam->is_published ||
                                $exam->results_approved_at
                            );

                        return [
                            'id' =>
                                $exam->id,

                            'name' =>
                                $exam->name,

                            'description' =>
                                $exam->description,

                            'subject' =>
                                $exam->subject->name
                                ?? null,

                            'exam_date' =>
                                $exam->exam_date,

                            'start_time' =>
                                $exam->start_time,

                            'end_time' =>
                                $exam->end_time,

                            'total_marks' =>
                                $exam->total_marks,

                            'passing_marks' =>
                                $exam->passing_marks,

                            'term' =>
                                $exam->term->name
                                ?? null,

                            'academic_year' =>
                                $exam->academic_year,

                            'is_published' =>
                                (bool)
                                $exam->is_published,

                            'result' =>
                                $showResult
                                    ? [
                                        'marks_obtained' =>
                                            $result
                                                ->marks_obtained,

                                        'percentage' =>
                                            $result
                                                ->percentage,

                                        'grade' =>
                                            $result->grade,

                                        'remarks' =>
                                            $result
                                                ->remarks,
                                    ]
                                    : null,
                        ];
                    }
                );

        return response()->json([
            'data' =>
                $formattedExams,
        ]);
    }

    public function downloadResults(
        Request $request,
        $student
    ): StreamedResponse|Response {
        $studentModel =
            $this->parentAccess
                ->assertCanAccessStudent(
                    $request->user(),
                    (int) $student
                );

        $format =
            strtolower(
                (string) $request->query(
                    'format',
                    'html'
                )
            );

        if (! in_array(
            $format,
            [
                'html',
                'csv',
            ],
            true
        )) {
            $format = 'html';
        }

        /*
         * Keep the existing Grade structure.
         * Do NOT assume grades has school_id.
         */
        $grades =
            Grade::query()
                ->where(
                    'student_id',
                    $studentModel->id
                )
                ->orderByDesc('year')
                ->orderBy('term')
                ->orderBy('subject')
                ->get();

        $examResults =
            ExamResult::query()
                ->where(
                    'student_id',
                    $studentModel->id
                )
                ->whereHas(
                    'exam',
                    function ($q) use (
                        $studentModel
                    ) {
                        $q->where(
                            'school_id',
                            $studentModel->school_id
                        )->where(
                            function ($inner) {
                                $inner->where(
                                    'is_published',
                                    true
                                )->orWhereNotNull(
                                    'results_approved_at'
                                );
                            }
                        );
                    }
                )
                ->with([
                    'exam:id,name,exam_date,total_marks,academic_year,term_id',
                    'exam.term:id,name',
                    'subject:id,name',
                ])
                ->orderByDesc(
                    'created_at'
                )
                ->get();

        $this->domainRules
            ->assertReportCardHasGrades(
                $studentModel,
                $grades,
                $examResults
            );

        $school =
            School::query()
                ->find(
                    $studentModel->school_id
                );

        $schoolName =
            $school?->name
            ?? 'School';

        $safeName =
            preg_replace(
                '/[^A-Za-z0-9_-]+/',
                '_',
                (string) (
                    $studentModel->full_name
                    ?: 'student'
                )
            )
            ?: 'student';

        $generatedAt =
            now()
                ->timezone('Africa/Harare')
                ->format(
                    'd M Y, H:i'
                );

        if ($format === 'csv') {
            $filename =
                "{$safeName}_results_".
                "{$studentModel->student_number}.csv";

            return response()->streamDownload(
                function () use (
                    $grades,
                    $examResults,
                    $studentModel,
                    $schoolName,
                    $generatedAt
                ) {
                    $out =
                        fopen(
                            'php://output',
                            'w'
                        );

                    fputcsv(
                        $out,
                        [
                            'School',
                            $schoolName,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Student',
                            $studentModel->full_name,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Student number',
                            $studentModel->student_number,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Class',
                            $studentModel->class,
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Generated',
                            $generatedAt,
                        ]
                    );

                    fputcsv(
                        $out,
                        []
                    );

                    fputcsv(
                        $out,
                        [
                            'Report card grades',
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Subject',
                            'Score',
                            'Total',
                            'Grade',
                            'Term',
                            'Year',
                        ]
                    );

                    foreach (
                        $grades as $grade
                    ) {
                        fputcsv(
                            $out,
                            [
                                $grade->subject,
                                $grade->score,
                                $grade->total,
                                $grade->grade,
                                $grade->term,
                                $grade->year,
                            ]
                        );
                    }

                    fputcsv(
                        $out,
                        []
                    );

                    fputcsv(
                        $out,
                        [
                            'Exam results',
                        ]
                    );

                    fputcsv(
                        $out,
                        [
                            'Exam',
                            'Subject',
                            'Exam date',
                            'Marks',
                            'Total',
                            'Percentage',
                            'Grade',
                            'Term',
                            'Year',
                        ]
                    );

                    foreach (
                        $examResults as $result
                    ) {
                        $examDate =
                            $result->exam?->exam_date;

                        fputcsv(
                            $out,
                            [
                                $result->exam?->name,
                                $result->subject?->name,
                                $examDate
                                    ? Carbon::parse(
                                        $examDate
                                    )->format(
                                        'd M Y'
                                    )
                                    : '',
                                $result->marks_obtained,
                                $result->total_marks,
                                $result->percentage,
                                $result->grade,
                                $result->exam?->term?->name,
                                $result->exam?->academic_year,
                            ]
                        );
                    }

                    fclose($out);
                },
                $filename,
                [
                    'Content-Type' =>
                        'text/csv; charset=UTF-8',
                ]
            );
        }

        $html =
            $this->buildReportCardHtml(
                $schoolName,
                $studentModel,
                $grades,
                $examResults,
                $generatedAt
            );

        $filename =
            "{$safeName}_report_card.html";

        return response(
            $html,
            200,
            [
                'Content-Type' =>
                    'text/html; charset=UTF-8',

                'Content-Disposition' =>
                    "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    private function buildReportCardHtml(
        string $schoolName,
        Student $student,
        $grades,
        $examResults,
        string $generatedAt,
    ): string {
        $gradeRows =
            $grades
                ->map(
                    function (Grade $grade) {
                        return '<tr>'
                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->subject
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->score
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->total
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->grade
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->term
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $grade->year
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'</tr>';
                    }
                )
                ->implode('');

        if ($gradeRows === '') {
            $gradeRows =
                '<tr>'.
                '<td colspan="6">'.
                'No report-card grades recorded yet.'.
                '</td>'.
                '</tr>';
        }

        $examRows =
            $examResults
                ->map(
                    function (
                        ExamResult $result
                    ) {
                        $examDate =
                            $result->exam?->exam_date
                                ? Carbon::parse(
                                    $result->exam->exam_date
                                )->format(
                                    'd M Y'
                                )
                                : '—';

                        return '<tr>'
                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->exam?->name
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->subject?->name
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e($examDate).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->marks_obtained
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->total_marks
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->percentage
                                        ?? '—'
                                    )
                                ).
                                '%</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result->grade
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'<td>'.
                                e(
                                    (string) (
                                        $result
                                            ->exam
                                            ?->term
                                            ?->name
                                        ?? '—'
                                    )
                                ).
                            '</td>'

                            .'</tr>';
                    }
                )
                ->implode('');

        if ($examRows === '') {
            $examRows =
                '<tr>'.
                '<td colspan="8">'.
                'No published exam results yet.'.
                '</td>'.
                '</tr>';
        }

        $title =
            e(
                (string) (
                    $student->full_name
                    ?? 'Student'
                )
            ).
            ' — Report card';

        $school =
            e($schoolName);

        $fullName =
            e(
                (string) (
                    $student->full_name
                    ?? '—'
                )
            );

        $studentNumber =
            e(
                (string) (
                    $student->student_number
                    ?? '—'
                )
            );

        $className =
            e(
                (string) (
                    $student->class
                    ?? '—'
                )
            );

        $generated =
            e($generatedAt);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>{$title}</title>

  <style>
    body {
      font-family: Georgia, "Times New Roman", serif;
      color: #111;
      margin: 32px;
    }

    h1,
    h2 {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0 0 8px;
    }

    .meta {
      color: #444;
      margin-bottom: 24px;
      font-size: 14px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 12px 0 28px;
      font-size: 14px;
    }

    th,
    td {
      border: 1px solid #bbb;
      padding: 8px 10px;
      text-align: left;
    }

    th {
      background: #f3f4f6;
      font-family: Arial, Helvetica, sans-serif;
    }

    .footer {
      margin-top: 32px;
      font-size: 12px;
      color: #666;
    }

    @media print {
      body {
        margin: 16px;
      }
    }
  </style>
</head>

<body>

  <h1>{$school}</h1>

  <h2>Student report card</h2>

  <div class="meta">

    <div>
      <strong>Student:</strong>
      {$fullName}
    </div>

    <div>
      <strong>Student number:</strong>
      {$studentNumber}
    </div>

    <div>
      <strong>Class:</strong>
      {$className}
    </div>

    <div>
      <strong>Generated:</strong>
      {$generated}
    </div>

  </div>

  <h2>
    Continuous assessment / report grades
  </h2>

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

    <tbody>
      {$gradeRows}
    </tbody>

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

    <tbody>
      {$examRows}
    </tbody>

  </table>

  <p class="footer">
    This document was generated from the school management system.
    Open and use Print → Save as PDF if needed.
  </p>

</body>
</html>
HTML;
    }

    public function createInvoice(
        Request $request,
        $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageFinance',
            ],
            permissionSlugs: [
                'finance.manage',
            ],
        );

        $user = $request->user();

        $studentModel =
            $this->findSchoolStudent(
                $request,
                $student
            );

        $schoolId =
            (int) $studentModel->school_id;

        $validator = Validator::make(
            $request->all(),
            [
                'amount' =>
                    'required_without:fee_group_id|nullable|numeric|min:0.01',

                'description' =>
                    'required_without:fee_group_id|nullable|string',

                'dueDate' =>
                    'required|date',

                'fee_structure_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'fee_structures',
                        'id'
                    )->where(
                        fn ($q) =>
                            $q->where(
                                'school_id',
                                $schoolId
                            )
                    ),
                ],

                'fee_group_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'fee_groups',
                        'id'
                    )->where(
                        fn ($q) =>
                            $q->where(
                                'school_id',
                                $schoolId
                            )->where(
                                'is_active',
                                true
                            )
                    ),
                ],

                'apply_discounts' =>
                    'nullable|boolean',

                'combine_group' =>
                    'nullable|boolean',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        $dueDate =
            new \DateTimeImmutable(
                $request->input('dueDate')
            );

        $applyDiscounts =
            $request->boolean(
                'apply_discounts',
                true
            );

        if ($request->filled(
            'fee_group_id'
        )) {
            $group =
                \App\Models\FeeGroup::query()
                    ->where(
                        'school_id',
                        $schoolId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->findOrFail(
                        (int) $request->input(
                            'fee_group_id'
                        )
                    );

            $invoices =
                $this->ledgerService
                    ->applyFeeGroup(
                        student:
                            $studentModel,
                        group: $group,
                        dueDate: $dueDate,
                        createdBy:
                            $user?->id,
                        applyDiscounts:
                            $applyDiscounts,
                        combine:
                            $request->boolean(
                                'combine_group',
                                false
                            ),
                    );

            if ($invoices->isEmpty()) {
                return response()->json([
                    'message' =>
                        'No fee structures found for this student\'s class in the selected fee group.',
                ], 422);
            }

            return response()->json([
                'data' =>
                    $invoices->first(),

                'meta' => [
                    'created_count' =>
                        $invoices->count(),

                    'invoice_ids' =>
                        $invoices->pluck('id'),
                ],

                'message' =>
                    $invoices->count() === 1
                        ? 'Invoice created successfully'
                        : "{$invoices->count()} invoices created from fee group",
            ], 201);
        }

        try {
            $invoice =
                $this->ledgerService
                    ->createInvoice(
                        student:
                            $studentModel,

                        amount:
                            (float) $request->input(
                                'amount'
                            ),

                        description:
                            (string) $request->input(
                                'description'
                            ),

                        feeStructureId:
                            $request->filled(
                                'fee_structure_id'
                            )
                                ? (int) $request->input(
                                    'fee_structure_id'
                                )
                                : null,

                        dueDate:
                            $dueDate,

                        createdBy:
                            $user?->id,

                        applyDiscounts:
                            $applyDiscounts,
                    );
        } catch (
            \InvalidArgumentException $e
        ) {
            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' =>
                $invoice,

            'message' =>
                'Invoice created successfully',
        ], 201);
    }

    public function promote(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $schoolId =
            $this->getSchoolId($request);

        $academicYear =
            $request->input(
                'academic_year',
                Setting::get(
                    'academic.academicYear',
                    (string) now()->year
                )
            );

        $nextAcademicYear =
            $request->input(
                'next_academic_year',
                (string) (
                    (int) $academicYear + 1
                )
            );

        $passMark =
            (float) $request->input(
                'pass_mark',
                50
            );

        $result =
            $this->promotionService
                ->runYearEnd(
                    schoolId:
                        $schoolId,

                    academicYear:
                        $academicYear,

                    nextAcademicYear:
                        $nextAcademicYear,

                    passMark:
                        $passMark,

                    studentIds:
                        null,

                    processedBy:
                        $request->user()->id,
                );

        return response()->json([
            'data' =>
                $result,

            'message' =>
                "Year-end complete: ".
                "{$result['promoted']} promoted, ".
                "{$result['repeated']} repeating, ".
                "{$result['graduated']} graduated",
        ]);
    }

    public function bulkInvoices(
        Request $request
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageFinance',
            ],
            permissionSlugs: [
                'finance.manage',
            ],
        );

        $user = $request->user();

        $schoolId =
            $this->getSchoolId($request);

        $validator = Validator::make(
            $request->all(),
            [
                'studentIds' =>
                    'required|array|min:1',

                'studentIds.*' =>
                    'required|integer',

                'description' =>
                    'required_without:fee_group_id|nullable|string',

                'dueDate' =>
                    'required|date',

                'amount' =>
                    'required_without:fee_group_id|nullable|numeric|min:0.01',

                'fee_structure_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'fee_structures',
                        'id'
                    )->where(
                        fn ($q) =>
                            $q->where(
                                'school_id',
                                $schoolId
                            )
                    ),
                ],

                'feeStructureId' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'fee_structures',
                        'id'
                    )->where(
                        fn ($q) =>
                            $q->where(
                                'school_id',
                                $schoolId
                            )
                    ),
                ],

                'fee_group_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'fee_groups',
                        'id'
                    )->where(
                        fn ($q) =>
                            $q->where(
                                'school_id',
                                $schoolId
                            )->where(
                                'is_active',
                                true
                            )
                    ),
                ],

                'apply_discounts' =>
                    'nullable|boolean',

                'combine_group' =>
                    'nullable|boolean',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        $dueDate =
            new \DateTimeImmutable(
                $request->input('dueDate')
            );

        $applyDiscounts =
            $request->boolean(
                'apply_discounts',
                true
            );

        $feeStructureId =
            $request->input(
                'fee_structure_id',
                $request->input(
                    'feeStructureId'
                )
            );

        $feeGroupId =
            $request->input(
                'fee_group_id'
            );

        $combineGroup =
            $request->boolean(
                'combine_group',
                false
            );

        /*
         * IMPORTANT:
         * Only load students from the current school.
         */
        $students =
            Student::query()
                ->where(
                    'school_id',
                    $schoolId
                )
                ->whereIn(
                    'id',
                    $request->input(
                        'studentIds'
                    )
                )
                ->get()
                ->keyBy('id');

        $created = 0;

        foreach (
            $request->input(
                'studentIds'
            ) as $studentId
        ) {
            $studentModel =
                $students->get(
                    (int) $studentId
                );

            if (! $studentModel) {
                continue;
            }

            if ($feeGroupId) {
                $group =
                    \App\Models\FeeGroup::query()
                        ->where(
                            'school_id',
                            $studentModel->school_id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->find(
                            (int) $feeGroupId
                        );

                if (! $group) {
                    continue;
                }

                $created +=
                    $this->ledgerService
                        ->applyFeeGroup(
                            student:
                                $studentModel,

                            group:
                                $group,

                            dueDate:
                                $dueDate,

                            createdBy:
                                $user?->id,

                            applyDiscounts:
                                $applyDiscounts,

                            combine:
                                $combineGroup,
                        )
                        ->count();

                continue;
            }

            try {
                $this->ledgerService
                    ->createInvoice(
                        student:
                            $studentModel,

                        amount:
                            (float) $request->input(
                                'amount'
                            ),

                        description:
                            (string) $request->input(
                                'description'
                            ),

                        feeStructureId:
                            $feeStructureId
                                ? (int) $feeStructureId
                                : null,

                        dueDate:
                            $dueDate,

                        createdBy:
                            $user?->id,

                        applyDiscounts:
                            $applyDiscounts,
                    );

                $created++;
            } catch (
                \InvalidArgumentException
            ) {
                continue;
            }
        }

        return response()->json([
            'data' => [
                'created' =>
                    $created,
            ],

            'message' =>
                "Invoices created for {$created} students",
        ], 201);
    }

    public function bulkStatus(
        Request $request
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $validator = Validator::make(
            $request->all(),
            [
                'studentIds' =>
                    'required|array|min:1',

                'studentIds.*' =>
                    'required|integer',

                'status' =>
                    'required|string|in:active,inactive,suspended,graduated',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        /*
         * This is one of the most important fixes.
         *
         * The old code:
         *
         * Student::whereIn('id', $request->studentIds)
         *
         * could update another school's students.
         *
         * This query first restricts by school.
         */
        $updated =
            $this->studentQuery($request)
                ->whereIn(
                    'id',
                    $request->input(
                        'studentIds'
                    )
                )
                ->update([
                    'status' =>
                        $request->input(
                            'status'
                        ),
                ]);

        return response()->json([
            'data' => [
                'updated' =>
                    $updated,
            ],

            'message' =>
                "Status updated for {$updated} students",
        ]);
    }

    public function bulkPromote(
        Request $request
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $validator = Validator::make(
            $request->all(),
            [
                'studentIds' =>
                    'required|array|min:1',

                'studentIds.*' =>
                    'required|integer',

                'academic_year' =>
                    'nullable|string',

                'next_academic_year' =>
                    'nullable|string',

                'pass_mark' =>
                    'nullable|numeric|min:0|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' =>
                    'Validation failed',
                'errors' =>
                    $validator->errors(),
            ], 422);
        }

        $schoolId =
            $this->getSchoolId($request);

        /*
         * Only pass IDs belonging to this school
         * to the promotion service.
         */
        $studentIds =
            Student::query()
                ->where(
                    'school_id',
                    $schoolId
                )
                ->whereIn(
                    'id',
                    $request->input(
                        'studentIds'
                    )
                )
                ->pluck('id')
                ->values()
                ->all();

        if (empty($studentIds)) {
            return response()->json([
                'message' =>
                    'No valid students were found for this school.',
            ], 422);
        }

        $academicYear =
            $request->input(
                'academic_year',
                Setting::get(
                    'academic.academicYear',
                    (string) now()->year
                )
            );

        $nextAcademicYear =
            $request->input(
                'next_academic_year',
                (string) (
                    (int) $academicYear + 1
                )
            );

        $result =
            $this->promotionService
                ->runYearEnd(
                    schoolId:
                        $schoolId,

                    academicYear:
                        $academicYear,

                    nextAcademicYear:
                        $nextAcademicYear,

                    passMark:
                        (float) $request->input(
                            'pass_mark',
                            50
                        ),

                    studentIds:
                        $studentIds,

                    processedBy:
                        $request->user()->id,
                );

        return response()->json([
            'data' =>
                $result,

            'message' =>
                "Promotion completed: ".
                "{$result['promoted']} promoted, ".
                "{$result['repeated']} repeated, ".
                "{$result['graduated']} graduated",
        ]);
    }

    public function uploadPhoto(
        Request $request,
        Student $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        /*
         * Route model binding is NOT trusted for tenancy.
         */
        $student =
            $this->resolveTenantStudent(
                $request,
                $student
            );

        $request->validate([
            'file' => [
                'required',
                'file',
                'image',
                'max:5120',
            ],
        ]);

        $schoolId =
            (int) $student->school_id;

        $result =
            app(FileUploadService::class)
                ->store(
                    $request->file('file'),
                    'student-photos',
                    'public',
                    $schoolId,
                );

        $student->update([
            'photo_url' =>
                $result['url'],
        ]);

        return response()->json([
            'data' => [
                'photo_url' =>
                    $result['url'],

                'student' =>
                    new \App\Http\Resources\Api\V1\StudentResource(
                        $student->fresh()
                    ),
            ],

            'message' =>
                'Student photo updated',
        ]);
    }

    public function bulkPrintIdCards(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $schoolId = $this->getSchoolId($request);

        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array|min:1|max:100',
            'student_ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = collect($request->input('student_ids'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $students = $this->studentQuery($request)
            ->with([
                'classModel:id,name,code',
                'gradeLevel:id,name,code',
                'house:id,name',
                'user:id,avatar_url',
            ])
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy('id');

        if ($students->count() !== $ids->count()) {
            return response()->json([
                'message' => 'One or more students could not be found for this school.',
                'errors' => ['student_ids' => ['Invalid student selected for ID card printing.']],
            ], 422);
        }

        $cards = $ids->map(
            fn (int $id) => $this->buildIdCardPayload($students->get($id))
        )->all();

        return response()->json([
            'data' => $cards,
            'school_id' => $schoolId,
        ]);
    }

    public function printIdCard(
        Request $request,
        Student $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: [
                'canManageStudents',
                'canManageTeachers',
            ],
            permissionSlugs: [
                'students.manage',
            ],
        );

        $student = $this->resolveTenantStudent($request, $student);

        $student->load([
            'classModel:id,name,code',
            'gradeLevel:id,name,code',
            'house:id,name',
            'user:id,avatar_url',
        ]);

        return response()->json([
            'data' => $this->buildIdCardPayload($student),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIdCardPayload(Student $student): array
    {
        $school = School::find($student->school_id);
        $branding = [];

        if ($school) {
            $settings = app(\App\Services\SchoolSettingsService::class)
                ->getAll($school, publicOnly: true);
            $branding = $settings['branding'] ?? [];
        }

        return [
            'document_number' => 'ID-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'issued_at' => now()->toIso8601String(),
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'student_number' => $student->student_number,
                'class' => $student->classModel?->name ?? $student->class,
                'grade_level' => $student->gradeLevel?->name,
                'house' => $student->house?->name,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'gender' => $student->gender,
                'photo_url' => $student->photo_url ?? $student->user?->avatar_url,
            ],
            'school' => [
                'id' => $school?->id,
                'name' => $branding['school_name'] ?? $school?->name,
                'code' => $school?->code,
                'address' => $school?->address,
                'phone' => $school?->phone,
                'email' => $school?->email,
                'logo_path' => $school?->logo_path,
                'motto' => $branding['motto'] ?? $school?->motto,
                'primary_color' => $branding['primary_color'] ?? '#FF7A00',
            ],
        ];
    }
}
