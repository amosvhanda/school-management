<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\Domain\SchoolDomainRules;
use App\Services\FileUploadService;
use App\Services\ParentAccessService;
use App\Services\StudentAdmissionService;
use App\Services\Students\StudentBulkActionService;
use App\Services\Students\StudentDocumentService;
use App\Services\Students\StudentIdCardService;
use App\Services\Students\StudentInvoiceService;
use App\Services\Students\StudentReportCardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function __construct(
        private ParentAccessService $parentAccess,
        private SchoolDomainRules $domainRules,
        private StudentAdmissionService $admissionService,
        private StudentReportCardService $reportCards,
        private StudentDocumentService $documents,
        private StudentIdCardService $idCards,
        private StudentInvoiceService $studentInvoices,
        private StudentBulkActionService $bulkActions,
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
                'guardian.firstName' => 'nullable|string|max:255',
                'guardian.surname' => 'nullable|string|max:255',
                'guardian.phone' => 'nullable|string|max:20',
                'guardian.email' => 'nullable|email|max:255',
                'guardian.relationship' => 'nullable|string|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
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
                    'message' => 'school_id is required when a super admin creates a student.',
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
            'first_name' => $request->input('firstName'),

            'last_name' => $request->input('surname'),

            'full_name' => trim(
                $request->input('firstName').
                ' '.
                $request->input('surname')
            ),

            'student_number' => $studentNumber,

            'class' => $request->input('class'),

            'date_of_birth' => $request->input('dateOfBirth'),

            'gender' => $request->input('gender'),

            'phone' => $request->input('phone'),

            'email' => $request->input('email'),

            'address' => $request->input('address'),

            'suburb' => $request->input('suburb'),

            /*
             * For normal users, the school name comes from
             * the authenticated tenant.
             */
            'school' => $schoolModel->name,

            'status' => 'active',

            /*
             * This is the critical tenant field.
             */
            'school_id' => $schoolId,

            'guardian_first_name' => $guardian['firstName']
                ?? $guardian['first_name']
                ?? null,

            'guardian_last_name' => $guardian['surname']
                ?? $guardian['last_name']
                ?? null,

            'guardian_phone' => $guardian['phone']
                ?? null,

            'guardian_email' => $guardian['email']
                ?? null,

            'guardian_relationship' => $guardian['relationship']
                ?? null,
        ]);

        return response()->json([
            'data' => $student,
            'message' => 'Student created successfully',
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
                'firstName' => 'sometimes|string|max:255',

                'surname' => 'sometimes|string|max:255',

                'class' => 'sometimes|string|max:255',

                'phone' => 'nullable|string|max:20',

                'email' => 'nullable|email|max:255',

                'address' => 'nullable|string',

                'suburb' => 'nullable|string|max:255',

                'guardian' => 'nullable|array',

                'guardian.firstName' => 'nullable|string|max:255',

                'guardian.surname' => 'nullable|string|max:255',

                'guardian.phone' => 'nullable|string|max:20',

                'guardian.email' => 'nullable|email|max:255',

                'guardian.relationship' => 'nullable|string|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
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
            'message' => 'Student updated successfully',
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
            'message' => 'Student deleted successfully',
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
                'student' => $studentModel,

                'grades' => $grades,

                'attendance' => $studentModel->attendance,

                'average_score' => $averageScore,

                'total_grades' => $totalGrades,
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
            'data' => $studentModel->invoices,
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

        $studentModel = $this->findSchoolStudent($request, $student);

        return response()->json([
            'data' => $this->documents->listForStudent($studentModel),
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

        $studentModel = $this->findSchoolStudent($request, $student);

        $validator = Validator::make(
            $request->all(),
            [
                'documents' => 'required|array|min:1',
                'documents.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
                'type' => 'nullable|string|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploaded = $this->documents->upload(
            $studentModel,
            $request->file('documents') ?? [],
            $request->user()?->id,
            $request->input('type'),
        );

        return response()->json([
            'message' => count($uploaded).' document(s) uploaded successfully',
            'data' => $uploaded,
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

        $studentModel = $this->findSchoolStudent($request, $student);
        $this->documents->destroy($studentModel, $document);

        return response()->json([
            'message' => 'Document deleted',
        ]);
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
                            'id' => $exam->id,

                            'name' => $exam->name,

                            'description' => $exam->description,

                            'subject' => $exam->subject->name
                                ?? null,

                            'exam_date' => $exam->exam_date,

                            'start_time' => $exam->start_time,

                            'end_time' => $exam->end_time,

                            'total_marks' => $exam->total_marks,

                            'passing_marks' => $exam->passing_marks,

                            'term' => $exam->term->name
                                ?? null,

                            'academic_year' => $exam->academic_year,

                            'is_published' => (bool)
                                $exam->is_published,

                            'result' => $showResult
                                    ? [
                                        'marks_obtained' => $result
                                            ->marks_obtained,

                                        'percentage' => $result
                                            ->percentage,

                                        'grade' => $result->grade,

                                        'remarks' => $result
                                            ->remarks,
                                    ]
                                    : null,
                        ];
                    }
                );

        return response()->json([
            'data' => $formattedExams,
        ]);
    }

    public function downloadResults(
        Request $request,
        $student
    ): StreamedResponse|Response {
        $studentModel = $this->parentAccess->assertCanAccessStudent(
            $request->user(),
            (int) $student
        );

        return $this->reportCards->download(
            $studentModel,
            (string) $request->query('format', 'html'),
        );
    }

    public function createInvoice(
        Request $request,
        $student
    ) {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $studentModel = $this->findSchoolStudent($request, $student);
        $schoolId = (int) $studentModel->school_id;

        $validator = Validator::make($request->all(), [
            'amount' => 'required_without:fee_group_id|nullable|numeric|min:0.01',
            'description' => 'required_without:fee_group_id|nullable|string',
            'dueDate' => 'required|date',
            'fee_structure_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_structures', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'fee_group_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_groups', 'id')->where(
                    fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)
                ),
            ],
            'apply_discounts' => 'nullable|boolean',
            'combine_group' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->studentInvoices->createForStudent(
                student: $studentModel,
                dueDate: new \DateTimeImmutable($request->input('dueDate')),
                createdBy: $request->user()?->id,
                applyDiscounts: $request->boolean('apply_discounts', true),
                feeGroupId: $request->filled('fee_group_id') ? (int) $request->input('fee_group_id') : null,
                combineGroup: $request->boolean('combine_group', false),
                amount: $request->filled('amount') ? (float) $request->input('amount') : null,
                description: $request->input('description'),
                feeStructureId: $request->filled('fee_structure_id') ? (int) $request->input('fee_structure_id') : null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payload = [
            'data' => $result['invoice'],
            'message' => $result['message'],
        ];

        if (($result['created_count'] ?? 1) > 1) {
            $payload['meta'] = [
                'created_count' => $result['created_count'],
                'invoice_ids' => $result['invoices']->pluck('id'),
            ];
        }

        return response()->json($payload, 201);
    }

    public function promote(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageStudents', 'canManageTeachers'],
            permissionSlugs: ['students.manage'],
        );

        $result = $this->bulkActions->promote(
            schoolId: $this->getSchoolId($request),
            studentIds: null,
            processedBy: $request->user()->id,
            academicYear: $request->input('academic_year'),
            nextAcademicYear: $request->input('next_academic_year'),
            passMark: (float) $request->input('pass_mark', 50),
        );

        return response()->json([
            'data' => $result,
            'message' => 'Year-end complete: '.
                "{$result['promoted']} promoted, ".
                "{$result['repeated']} repeating, ".
                "{$result['graduated']} graduated",
        ]);
    }

    public function bulkInvoices(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $schoolId = $this->getSchoolId($request);

        $validator = Validator::make($request->all(), [
            'studentIds' => 'required|array|min:1',
            'studentIds.*' => 'required|integer',
            'description' => 'required_without:fee_group_id|nullable|string',
            'dueDate' => 'required|date',
            'amount' => 'required_without:fee_group_id|nullable|numeric|min:0.01',
            'fee_structure_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_structures', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'feeStructureId' => [
                'nullable',
                'integer',
                Rule::exists('fee_structures', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'fee_group_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_groups', 'id')->where(
                    fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true)
                ),
            ],
            'apply_discounts' => 'nullable|boolean',
            'combine_group' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feeStructureId = $request->input('fee_structure_id', $request->input('feeStructureId'));

        $result = $this->studentInvoices->createBulk(
            schoolId: $schoolId,
            studentIds: $request->input('studentIds'),
            dueDate: new \DateTimeImmutable($request->input('dueDate')),
            createdBy: $request->user()?->id,
            applyDiscounts: $request->boolean('apply_discounts', true),
            feeGroupId: $request->filled('fee_group_id') ? (int) $request->input('fee_group_id') : null,
            combineGroup: $request->boolean('combine_group', false),
            amount: $request->filled('amount') ? (float) $request->input('amount') : null,
            description: $request->input('description'),
            feeStructureId: $feeStructureId ? (int) $feeStructureId : null,
        );

        return response()->json([
            'data' => ['created' => $result['created']],
            'message' => "Invoices created for {$result['created']} students",
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
            'studentIds' => 'required|array|min:1',
            'studentIds.*' => 'required|integer',
            'status' => 'required|string|in:active,inactive,suspended,graduated',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->bulkActions->updateStatus(
            $this->studentQuery($request),
            $request->input('studentIds'),
            (string) $request->input('status'),
        );

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
            'studentIds' => 'required|array|min:1',
            'studentIds.*' => 'required|integer',
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

        try {
            $result = $this->bulkActions->promote(
                schoolId: $this->getSchoolId($request),
                studentIds: $request->input('studentIds'),
                processedBy: $request->user()->id,
                academicYear: $request->input('academic_year'),
                nextAcademicYear: $request->input('next_academic_year'),
                passMark: (float) $request->input('pass_mark', 50),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $result,
            'message' => 'Promotion completed: '.
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
            'photo_url' => $result['url'],
        ]);

        return response()->json([
            'data' => [
                'photo_url' => $result['url'],

                'student' => new StudentResource(
                    $student->fresh()
                ),
            ],

            'message' => 'Student photo updated',
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
            fn (int $id) => $this->idCards->buildPayload($students->get($id))
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
            'data' => $this->idCards->buildPayload($student),
        ]);
    }
}
