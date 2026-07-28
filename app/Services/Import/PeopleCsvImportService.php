<?php

namespace App\Services\Import;

use App\Models\ClassModel;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GuardianService;
use App\Services\StaffNumberService;
use App\Services\StudentAdmissionService;
use App\Services\StudentPlacementService;
use App\Support\Csv\CsvReader;
use App\Support\Csv\DryRunRollback;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class PeopleCsvImportService
{
    public function __construct(
        private CsvReader $csv,
        private StudentAdmissionService $admission,
        private StudentPlacementService $placement,
        private GuardianService $guardians,
        private StaffNumberService $staffNumbers,
    ) {}

    /**
     * @return array{created:int, updated:int, failed:int, errors:list<array{line:int, message:string}>}
     */
    public function importStudents(UploadedFile $file, int $schoolId, ?int $actorId = null, bool $dryRun = false): array
    {
        $rows = $this->csv->read(
            $file->getRealPath(),
            $this->studentHeaderAliases(),
            ['first_name', 'last_name', 'class'],
        );

        return $this->runRows($rows, function (array $data, int $line) use ($schoolId, $actorId): string {
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $className = $data['class'] ?? '';

            if ($firstName === '' || $lastName === '') {
                throw new \RuntimeException('first_name and last_name are required.');
            }
            if ($className === '') {
                throw new \RuntimeException('class is required and must match an existing class name.');
            }

            $class = ClassModel::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) use ($className) {
                    $q->where('name', $className)
                        ->orWhere('form', $className);
                })
                ->first();

            if (! $class) {
                throw new \RuntimeException("Class \"{$className}\" was not found. Create the class before importing.");
            }

            $studentNumber = trim((string) ($data['student_number'] ?? ''));
            $existing = null;
            if ($studentNumber !== '') {
                $existing = Student::query()
                    ->where('school_id', $schoolId)
                    ->where('student_number', $studentNumber)
                    ->first();
            }

            $guardianData = $this->guardianPayload($data);
            $academicYear = (string) (School::query()->whereKey($schoolId)->value('academic_year') ?: date('Y'));

            if ($existing) {
                $previousClassId = (int) ($existing->class_id ?? 0);
                $existing->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => trim("{$firstName} {$lastName}"),
                    'date_of_birth' => $this->nullableDate($data['date_of_birth'] ?? null),
                    'gender' => $this->nullableString($data['gender'] ?? null),
                    'phone' => $this->nullableString($data['phone'] ?? null),
                    'email' => $this->nullableString($data['email'] ?? null),
                    'address' => $this->nullableString($data['address'] ?? null),
                    'status' => $this->nullableString($data['status'] ?? null) ?? $existing->status,
                    'class' => $class->name,
                    'class_id' => $class->id,
                    'grade_level_id' => $class->grade_level_id ?? $existing->grade_level_id,
                    'guardian_first_name' => $guardianData['first_name'] ?? $existing->guardian_first_name,
                    'guardian_last_name' => $guardianData['last_name'] ?? $existing->guardian_last_name,
                    'guardian_phone' => $guardianData['phone'] ?? $existing->guardian_phone,
                    'guardian_email' => $guardianData['email'] ?? $existing->guardian_email,
                    'guardian_relationship' => $guardianData['relationship'] ?? $existing->guardian_relationship,
                ]);

                if ($guardianData !== []) {
                    $this->guardians->createOrFindGuardian($guardianData, $schoolId, $existing->id);
                }

                if ($previousClassId !== (int) $class->id) {
                    $this->placement->place($existing->fresh(), [
                        'class_id' => (int) $class->id,
                        'academic_year' => $academicYear,
                        'reason' => 'csv_import',
                        'apply_fees' => false,
                    ], $actorId);
                }

                return 'updated';
            }

            $student = $this->admission->admitStudent([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'student_number' => $studentNumber !== '' ? $studentNumber : null,
                'date_of_birth' => $this->nullableDate($data['date_of_birth'] ?? null),
                'gender' => $this->nullableString($data['gender'] ?? null),
                'phone' => $this->nullableString($data['phone'] ?? null),
                'email' => $this->nullableString($data['email'] ?? null),
                'address' => $this->nullableString($data['address'] ?? null),
                'class_id' => $class->id,
                'grade_level_id' => $class->grade_level_id,
            ], $guardianData, $schoolId);

            $student->update(['class' => $class->name]);

            $this->placement->place($student->fresh(), [
                'class_id' => (int) $class->id,
                'academic_year' => $academicYear,
                'reason' => 'admission',
                'apply_fees' => false,
            ], $actorId);

            return 'created';
        }, $dryRun);
    }

    /**
     * @return array{created:int, updated:int, failed:int, errors:list<array{line:int, message:string}>, logins_created?:int}
     */
    public function importTeachers(UploadedFile $file, int $schoolId, bool $createLoginUsers = false, bool $dryRun = false): array
    {
        $rows = $this->csv->read(
            $file->getRealPath(),
            $this->teacherHeaderAliases(),
            ['first_name', 'last_name', 'email'],
        );

        $school = School::findOrFail($schoolId);
        $defaultCurrency = $school->getDefaultCurrency() ?? 'USD';
        $loginsCreated = 0;

        $result = $this->runRows($rows, function (array $data) use ($schoolId, $defaultCurrency, $createLoginUsers, &$loginsCreated): string {
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if ($firstName === '' || $lastName === '' || $email === '') {
                throw new \RuntimeException('first_name, last_name, and email are required.');
            }

            $existing = Teacher::query()
                ->where('school_id', $schoolId)
                ->where('email', $email)
                ->first();

            $designationId = $this->resolveDesignationId($schoolId, $data['designation'] ?? null);
            $payload = [
                'name' => trim("{$firstName} {$lastName}"),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $this->nullableString($data['phone'] ?? null),
                'address' => $this->nullableString($data['address'] ?? null),
                'subject' => $this->nullableString($data['subject'] ?? null),
                'department' => $this->nullableString($data['department'] ?? null),
                'designation_id' => $designationId,
                'qualification' => $this->nullableString($data['qualification'] ?? null),
                'joining_date' => $this->nullableDate($data['joining_date'] ?? null),
                'status' => $this->nullableString($data['status'] ?? null) ?? 'active',
                'employment_type' => $this->nullableString($data['employment_type'] ?? null) ?? 'full_time',
                'salary_currency' => $defaultCurrency,
            ];

            if ($existing) {
                $employeeId = trim((string) ($data['employee_id'] ?? $data['employee_number'] ?? ''));
                if ($employeeId !== '') {
                    $payload['employee_id'] = $employeeId;
                }
                $existing->update($payload);
                if ($createLoginUsers && $this->ensureTeacherLoginUser($existing->fresh())) {
                    $loginsCreated++;
                }

                return 'updated';
            }

            $employeeId = trim((string) ($data['employee_id'] ?? $data['employee_number'] ?? ''));
            $teacher = Teacher::create([
                ...$payload,
                'employee_id' => $employeeId !== ''
                    ? $employeeId
                    : $this->staffNumbers->generateEmployeeNumber($schoolId),
                'school_id' => $schoolId,
            ]);

            if ($createLoginUsers && $this->ensureTeacherLoginUser($teacher)) {
                $loginsCreated++;
            }

            return 'created';
        }, $dryRun);

        if ($createLoginUsers) {
            $result['logins_created'] = $dryRun ? 0 : $loginsCreated;
        }

        return $result;
    }

    /**
     * @return array{created:int, updated:int, failed:int, errors:list<array{line:int, message:string}>}
     */
    public function importEmployees(UploadedFile $file, int $schoolId, bool $dryRun = false): array
    {
        $rows = $this->csv->read(
            $file->getRealPath(),
            $this->employeeHeaderAliases(),
            ['first_name', 'last_name'],
        );

        $school = School::findOrFail($schoolId);
        $defaultCurrency = $school->getDefaultCurrency() ?? 'USD';

        return $this->runRows($rows, function (array $data) use ($schoolId, $defaultCurrency): string {
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            if ($firstName === '' || $lastName === '') {
                throw new \RuntimeException('first_name and last_name are required.');
            }

            $employeeNumber = trim((string) ($data['employee_number'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            $existing = null;
            if ($employeeNumber !== '') {
                $existing = Employee::query()
                    ->where('school_id', $schoolId)
                    ->where('employee_number', $employeeNumber)
                    ->first();
            }
            if (! $existing && $email !== '') {
                $existing = Employee::query()
                    ->where('school_id', $schoolId)
                    ->where('email', $email)
                    ->first();
            }

            $payload = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim("{$firstName} {$lastName}"),
                'email' => $email !== '' ? $email : null,
                'phone' => $this->nullableString($data['phone'] ?? null),
                'designation_id' => $this->resolveDesignationId($schoolId, $data['designation'] ?? null),
                'department_id' => $this->resolveDepartmentId($schoolId, $data['department'] ?? null),
                'joining_date' => $this->nullableDate($data['joining_date'] ?? null),
                'employment_type' => $this->nullableString($data['employment_type'] ?? null) ?? 'full_time',
                'status' => $this->nullableString($data['status'] ?? null) ?? 'active',
                'base_salary' => $this->nullableNumber($data['base_salary'] ?? null),
                'salary_currency' => $this->nullableString($data['salary_currency'] ?? null) ?? $defaultCurrency,
                'notes' => $this->nullableString($data['notes'] ?? null),
            ];

            if ($existing) {
                if ($employeeNumber !== '') {
                    $payload['employee_number'] = $employeeNumber;
                }
                $existing->update($payload);

                return 'updated';
            }

            Employee::create([
                ...$payload,
                'employee_number' => $employeeNumber !== ''
                    ? $employeeNumber
                    : $this->staffNumbers->generateEmployeeNumber($schoolId),
                'school_id' => $schoolId,
            ]);

            return 'created';
        }, $dryRun);
    }

    /**
     * @return array{created:int, updated:int, failed:int, errors:list<array{line:int, message:string}>}
     */
    public function importGuardians(UploadedFile $file, int $schoolId, bool $dryRun = false): array
    {
        $rows = $this->csv->read(
            $file->getRealPath(),
            $this->guardianHeaderAliases(),
            ['first_name', 'last_name'],
        );

        return $this->runRows($rows, function (array $data) use ($schoolId): string {
            $firstName = trim((string) ($data['first_name'] ?? ''));
            $lastName = trim((string) ($data['last_name'] ?? ''));
            $phone = trim((string) ($data['phone'] ?? ''));
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if ($firstName === '' || $lastName === '') {
                throw new \RuntimeException('first_name and last_name are required.');
            }
            if ($phone === '' && $email === '') {
                throw new \RuntimeException('phone or email is required to match existing guardians.');
            }

            $studentId = null;
            $studentNumber = trim((string) ($data['student_number'] ?? ''));
            if ($studentNumber !== '') {
                $student = Student::query()
                    ->where('school_id', $schoolId)
                    ->where('student_number', $studentNumber)
                    ->first();
                if (! $student) {
                    throw new \RuntimeException("Student \"{$studentNumber}\" was not found.");
                }
                $studentId = $student->id;
            }

            $existing = Guardian::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) use ($email, $phone) {
                    if ($email !== '') {
                        $q->where('email', $email);
                    }
                    if ($phone !== '') {
                        $q->orWhere('phone', $phone);
                    }
                })
                ->first();

            $guardianData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email !== '' ? $email : null,
                'relationship' => $this->nullableString($data['relationship'] ?? null) ?? 'parent',
                'address' => $this->nullableString($data['address'] ?? null),
                'national_id' => $this->nullableString($data['national_id'] ?? null),
                'occupation' => $this->nullableString($data['occupation'] ?? null),
                'is_primary' => true,
            ];

            $this->guardians->createOrFindGuardian($guardianData, $schoolId, $studentId);

            return $existing ? 'updated' : 'created';
        }, $dryRun);
    }

    /**
     * @return list<string>
     */
    public function templateHeaders(string $type): array
    {
        return match ($type) {
            'students' => [
                'student_number',
                'first_name',
                'last_name',
                'class',
                'date_of_birth',
                'gender',
                'phone',
                'email',
                'address',
                'status',
                'guardian_first_name',
                'guardian_last_name',
                'guardian_phone',
                'guardian_email',
                'guardian_relationship',
            ],
            'teachers' => [
                'employee_id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'subject',
                'department',
                'designation',
                'qualification',
                'joining_date',
                'employment_type',
                'status',
                'address',
            ],
            'employees' => [
                'employee_number',
                'first_name',
                'last_name',
                'email',
                'phone',
                'designation',
                'department',
                'joining_date',
                'employment_type',
                'status',
                'base_salary',
                'salary_currency',
                'notes',
            ],
            'guardians' => [
                'first_name',
                'last_name',
                'phone',
                'email',
                'relationship',
                'address',
                'national_id',
                'occupation',
                'student_number',
            ],
            default => throw new \InvalidArgumentException('Unknown import type.'),
        };
    }

    /**
     * @param  list<array{line:int, data:array<string, string>}>  $rows
     * @param  callable(array<string, string>, int): string  $handler
     * @return array{created:int, updated:int, failed:int, errors:list<array{line:int, message:string}>}
     */
    private function runRows(array $rows, callable $handler, bool $dryRun = false): array
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $row) {
            try {
                if ($dryRun) {
                    $action = null;
                    try {
                        DB::transaction(function () use ($handler, $row, &$action) {
                            $action = $handler($row['data'], $row['line']);
                            throw new DryRunRollback('dry-run');
                        });
                    } catch (DryRunRollback) {
                        // Row validated and rolled back.
                    }
                } else {
                    $action = DB::transaction(fn () => $handler($row['data'], $row['line']));
                }

                if ($action === 'updated') {
                    $updated++;
                } else {
                    $created++;
                }
            } catch (Throwable $e) {
                $failed++;
                $errors[] = [
                    'line' => $row['line'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return compact('created', 'updated', 'failed', 'errors');
    }

    /**
     * @return array<string, string>
     */
    private function studentHeaderAliases(): array
    {
        return [
            'student_number' => 'student_number',
            'admission_number' => 'student_number',
            'admission_no' => 'student_number',
            'first_name' => 'first_name',
            'firstname' => 'first_name',
            'last_name' => 'last_name',
            'lastname' => 'last_name',
            'surname' => 'last_name',
            'class' => 'class',
            'class_name' => 'class',
            'form' => 'class',
            'date_of_birth' => 'date_of_birth',
            'dob' => 'date_of_birth',
            'birth_date' => 'date_of_birth',
            'gender' => 'gender',
            'sex' => 'gender',
            'phone' => 'phone',
            'mobile' => 'phone',
            'email' => 'email',
            'address' => 'address',
            'status' => 'status',
            'guardian_first_name' => 'guardian_first_name',
            'parent_first_name' => 'guardian_first_name',
            'guardian_last_name' => 'guardian_last_name',
            'guardian_surname' => 'guardian_last_name',
            'parent_last_name' => 'guardian_last_name',
            'guardian_phone' => 'guardian_phone',
            'parent_phone' => 'guardian_phone',
            'guardian_email' => 'guardian_email',
            'parent_email' => 'guardian_email',
            'guardian_relationship' => 'guardian_relationship',
            'relationship' => 'guardian_relationship',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function teacherHeaderAliases(): array
    {
        return [
            'employee_id' => 'employee_id',
            'employee_number' => 'employee_number',
            'staff_number' => 'employee_id',
            'first_name' => 'first_name',
            'firstname' => 'first_name',
            'last_name' => 'last_name',
            'lastname' => 'last_name',
            'surname' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'subject' => 'subject',
            'department' => 'department',
            'designation' => 'designation',
            'qualification' => 'qualification',
            'joining_date' => 'joining_date',
            'start_date' => 'joining_date',
            'employment_type' => 'employment_type',
            'status' => 'status',
            'address' => 'address',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function employeeHeaderAliases(): array
    {
        return [
            'employee_number' => 'employee_number',
            'employee_id' => 'employee_number',
            'staff_number' => 'employee_number',
            'first_name' => 'first_name',
            'firstname' => 'first_name',
            'last_name' => 'last_name',
            'lastname' => 'last_name',
            'surname' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'mobile' => 'phone',
            'designation' => 'designation',
            'department' => 'department',
            'joining_date' => 'joining_date',
            'start_date' => 'joining_date',
            'employment_type' => 'employment_type',
            'status' => 'status',
            'base_salary' => 'base_salary',
            'salary' => 'base_salary',
            'salary_currency' => 'salary_currency',
            'currency' => 'salary_currency',
            'notes' => 'notes',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function guardianHeaderAliases(): array
    {
        return [
            'first_name' => 'first_name',
            'firstname' => 'first_name',
            'last_name' => 'last_name',
            'lastname' => 'last_name',
            'surname' => 'last_name',
            'phone' => 'phone',
            'mobile' => 'phone',
            'email' => 'email',
            'relationship' => 'relationship',
            'address' => 'address',
            'national_id' => 'national_id',
            'id_number' => 'national_id',
            'occupation' => 'occupation',
            'student_number' => 'student_number',
            'admission_number' => 'student_number',
        ];
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function guardianPayload(array $data): array
    {
        $first = trim((string) ($data['guardian_first_name'] ?? ''));
        $last = trim((string) ($data['guardian_last_name'] ?? ''));
        $phone = trim((string) ($data['guardian_phone'] ?? ''));
        $email = trim((string) ($data['guardian_email'] ?? ''));

        if ($first === '' && $last === '' && $phone === '' && $email === '') {
            return [];
        }

        return [
            'first_name' => $first !== '' ? $first : 'Guardian',
            'last_name' => $last !== '' ? $last : 'Contact',
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'relationship' => $this->nullableString($data['guardian_relationship'] ?? null) ?? 'parent',
            'is_primary' => true,
        ];
    }

    private function resolveDesignationId(int $schoolId, ?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return Designation::query()
            ->where('school_id', $schoolId)
            ->where('name', $name)
            ->value('id');
    }

    private function resolveDepartmentId(int $schoolId, ?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return Department::query()
            ->where('school_id', $schoolId)
            ->where('name', $name)
            ->value('id');
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new \RuntimeException("Invalid date: {$value}");
        }

        return date('Y-m-d', $timestamp);
    }

    private function nullableNumber(?string $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \RuntimeException("Invalid number: {$value}");
        }

        return (float) $value;
    }

    /**
     * Create or link a teacher login user. Returns true when a new user was created.
     */
    private function ensureTeacherLoginUser(Teacher $teacher): bool
    {
        if ($teacher->user_id) {
            return false;
        }

        $email = strtolower(trim((string) $teacher->email));
        if ($email === '') {
            throw new \RuntimeException('Email is required to create a login user.');
        }

        $existingUser = User::query()
            ->where('school_id', $teacher->school_id)
            ->where('email', $email)
            ->first();

        if ($existingUser) {
            $teacher->update(['user_id' => $existingUser->id]);
            if ($existingUser->role?->value !== 'teacher' && (string) $existingUser->role !== 'teacher') {
                $existingUser->update(['role' => 'teacher']);
            }

            return false;
        }

        $user = User::create([
            'name' => $teacher->name ?: trim($teacher->first_name.' '.$teacher->last_name),
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'email' => $email,
            'phone' => $teacher->phone,
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'school_id' => $teacher->school_id,
        ]);

        $teacher->update(['user_id' => $user->id]);

        return true;
    }
}
