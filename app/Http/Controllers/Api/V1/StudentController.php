<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Requests\Api\V1\Student\StoreStudentRequest;
use App\Http\Requests\Api\V1\Student\UpdateStudentRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\CustomField;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\CustomFieldService;
use App\Services\GuardianService;
use App\Services\StudentAdmissionService;
use App\Services\StudentPlacementService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class StudentController extends Controller
{
    use HandlesResourceQueries;

    public function __construct(
        private CustomFieldService $customFieldService,
        private GuardianService $guardianService,
        private StudentAdmissionService $admissionService,
        private StudentPlacementService $placementService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        return $this->paginateResource($request, Student::class, StudentResource::class, [
            'filters' => [
                'status',
                'class_id',
                'grade_level_id',
                'gender',
                'class',
                AllowedFilter::callback('guardian_id', function ($query, $value) {
                    $query->whereHas('guardians', fn ($q) => $q->where('guardians.id', (int) $value));
                }),
                'search',
            ],
            'search_columns' => ['full_name', 'first_name', 'last_name', 'student_number', 'email'],
            'sorts' => ['full_name', 'created_at', 'student_number', 'status'],
            'includes' => ['guardians', 'classModel', 'gradeLevel', 'studentCategory'],
            'fields' => [
                'students.id',
                'students.full_name',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.status',
                'students.class_id',
                'students.grade_level_id',
                'students.student_category_id',
                'students.email',
                'students.phone',
                'students.school_id',
                'students.created_at',
            ],
            'default_sort' => '-created_at',
            'with' => ['guardians', 'classModel', 'gradeLevel', 'studentCategory'],
        ]);
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);

        return $this->success(new StudentResource($student->load(['guardians', 'studentCategory'])));
    }

    public function store(StoreStudentRequest $request)
    {
        $guardian = $request->input('guardian', []);
        $schoolId = $request->user()->school_id;

        $student = Student::create([
            'first_name' => $request->firstName,
            'last_name' => $request->surname,
            'full_name' => trim($request->firstName.' '.$request->surname),
            'student_number' => $this->admissionService->generateStudentNumber((int) $schoolId),
            'class' => $request->class,
            'class_id' => $request->class_id,
            'stream_id' => $request->input('stream_id'),
            'house_id' => $request->input('house_id'),
            'grade_level_id' => $request->grade_level_id,
            'student_category_id' => $request->input('student_category_id'),
            'date_of_birth' => $request->dateOfBirth,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'suburb' => $request->suburb,
            'school' => $request->school,
            'status' => 'active',
            'school_id' => $schoolId,
            'guardian_first_name' => $guardian['firstName'] ?? $guardian['first_name'] ?? null,
            'guardian_last_name' => $guardian['surname'] ?? $guardian['last_name'] ?? null,
            'guardian_phone' => $guardian['phone'] ?? null,
            'guardian_email' => $guardian['email'] ?? null,
            'guardian_relationship' => $guardian['relationship'] ?? null,
        ]);

        if ($request->filled('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $student,
                CustomField::ENTITY_STUDENT,
                $request->input('custom_fields', [])
            );
        }

        $this->syncStudentGuardian(
            $student,
            $guardian,
            $request->input('guardian_id'),
            $schoolId,
        );

        if ($request->filled('class_id')) {
            $academicYear = (string) ($request->input('academic_year')
                ?? $request->user()?->school?->academic_year
                ?? date('Y'));
            $this->placementService->place($student->fresh(), [
                'class_id' => (int) $request->class_id,
                'stream_id' => $request->input('stream_id'),
                'house_id' => $request->input('house_id'),
                'academic_year' => $academicYear,
                'reason' => 'admission',
                'apply_fees' => false,
            ], $request->user()?->id);
        }

        return $this->created(
            new StudentResource($student->fresh()->load(['guardians', 'classModel', 'stream', 'house'])),
            'Student created successfully',
        );
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        if ($request->has('firstName') || $request->has('surname')) {
            $student->first_name = $request->firstName ?? $student->first_name;
            $student->last_name = $request->surname ?? $student->last_name;
            $student->full_name = trim($student->first_name.' '.$student->last_name);
        }

        $student->fill($request->only(['class', 'class_id', 'grade_level_id', 'student_category_id', 'phone', 'email', 'address', 'suburb', 'status']));

        if ($request->has('dateOfBirth')) {
            $student->date_of_birth = $request->dateOfBirth;
        }
        if ($request->has('gender')) {
            $student->gender = $request->gender;
        }

        $guardian = $request->input('guardian', []);
        if ($guardian !== []) {
            $student->guardian_first_name = $guardian['firstName'] ?? $guardian['first_name'] ?? $student->guardian_first_name;
            $student->guardian_last_name = $guardian['surname'] ?? $guardian['last_name'] ?? $student->guardian_last_name;
            $student->guardian_phone = $guardian['phone'] ?? $student->guardian_phone;
            $student->guardian_email = $guardian['email'] ?? $student->guardian_email;
            $student->guardian_relationship = $guardian['relationship'] ?? $student->guardian_relationship;
        }

        $student->save();

        if ($request->has('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $student,
                CustomField::ENTITY_STUDENT,
                $request->input('custom_fields', [])
            );
        }

        if ($request->has('guardian_id') || $guardian !== []) {
            $this->syncStudentGuardian(
                $student,
                $guardian,
                $request->input('guardian_id'),
                $request->user()->school_id,
            );
        }

        return $this->success(
            new StudentResource($student->fresh()->load('guardians')),
            'Student updated successfully',
        );
    }

    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);
        $student->delete();

        return $this->success(message: 'Student deleted successfully');
    }

    /**
     * Link an existing guardian or create/find one, then attach to the student pivot.
     */
    private function syncStudentGuardian(
        Student $student,
        array $guardianInput,
        mixed $guardianId,
        int $schoolId,
    ): void {
        if ($guardianId) {
            $guardian = Guardian::where('school_id', $schoolId)->findOrFail((int) $guardianId);
            $pivot = [
                'relationship' => $guardianInput['relationship'] ?? $guardian->relationship ?? 'parent',
                'is_primary' => true,
                'can_pickup' => true,
                'emergency_contact' => false,
            ];

            $guardian->students()->syncWithoutDetaching([$student->id => $pivot]);
            $this->guardianService->syncParentUserLink($guardian, $student->id, $pivot);
            $this->syncDenormalizedGuardianFields($student, $guardian, $pivot['relationship']);

            return;
        }

        $hasData = ! empty($guardianInput['firstName'])
            || ! empty($guardianInput['first_name'])
            || ! empty($guardianInput['phone']);

        if (! $hasData) {
            return;
        }

        $guardianData = [
            'first_name' => $guardianInput['firstName'] ?? $guardianInput['first_name'] ?? '',
            'last_name' => $guardianInput['surname'] ?? $guardianInput['last_name'] ?? '',
            'phone' => $guardianInput['phone'] ?? '',
            'email' => $guardianInput['email'] ?? null,
            'relationship' => $guardianInput['relationship'] ?? 'parent',
            'is_primary' => true,
        ];

        $guardian = $this->guardianService->createOrFindGuardian($guardianData, $schoolId, $student->id);
        $this->syncDenormalizedGuardianFields(
            $student,
            $guardian,
            $guardianData['relationship'] ?? 'parent',
        );
    }

    private function syncDenormalizedGuardianFields(Student $student, Guardian $guardian, ?string $relationship): void
    {
        $student->update([
            'guardian_first_name' => $guardian->first_name,
            'guardian_last_name' => $guardian->last_name,
            'guardian_phone' => $guardian->phone,
            'guardian_email' => $guardian->email,
            'guardian_relationship' => $relationship ?? $guardian->relationship ?? 'parent',
        ]);
    }
}
