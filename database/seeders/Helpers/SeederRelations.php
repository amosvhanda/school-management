<?php

namespace Database\Seeders\Helpers;

use App\Models\ClassModel;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Keeps class, grade level, guardian, and parent links aligned for forms and filters.
 */
final class SeederRelations
{
    /**
     * @return array<string, GradeLevel>
     */
    public static function gradeLevelsByName(School $school): array
    {
        return GradeLevel::query()
            ->where('school_id', $school->id)
            ->get()
            ->keyBy('name')
            ->all();
    }

    public static function resolveGradeLevelId(School $school, string $className): ?int
    {
        $levels = self::gradeLevelsByName($school);
        $levelName = ZimbabweData::gradeLevelNameForClass($className);

        return $levels[$levelName]->id ?? null;
    }

    public static function syncClassGradeLevels(School $school): void
    {
        $levels = self::gradeLevelsByName($school);

        ClassModel::query()
            ->where('school_id', $school->id)
            ->each(function (ClassModel $class) use ($levels): void {
                $levelName = ZimbabweData::gradeLevelNameForClass($class->name);
                $gradeLevel = $levels[$levelName] ?? null;

                if ($gradeLevel && $class->grade_level_id !== $gradeLevel->id) {
                    $class->grade_level_id = $gradeLevel->id;
                    $class->save();
                }
            });
    }

    public static function syncStudentGradeLevels(School $school): void
    {
        $classes = ClassModel::query()
            ->where('school_id', $school->id)
            ->get()
            ->keyBy('id');

        Student::query()
            ->where('school_id', $school->id)
            ->each(function (Student $student) use ($school, $classes): void {
                $gradeLevelId = null;

                if ($student->class_id && $classes->has($student->class_id)) {
                    $gradeLevelId = $classes->get($student->class_id)?->grade_level_id;
                }

                if (!$gradeLevelId && $student->class) {
                    $gradeLevelId = self::resolveGradeLevelId($school, $student->class);
                }

                if ($gradeLevelId && $student->grade_level_id !== $gradeLevelId) {
                    $student->grade_level_id = $gradeLevelId;
                    $student->save();
                }
            });
    }

    public static function guardianEmailForStudent(Student $student, string $firstName, string $lastName): string
    {
        $schoolCode = strtolower(optional($student->school)->code ?? 'school');

        return strtolower("{$firstName}.{$lastName}.{$student->id}@{$schoolCode}.guardian.co.zw");
    }

    public static function linkGuardianToStudent(
        School $school,
        Student $student,
        string $firstName,
        string $lastName,
        string $phone,
        string $relationship,
        bool $isPrimary = true,
        ?string $email = null,
    ): Guardian {
        $email ??= self::guardianEmailForStudent($student, $firstName, $lastName);

        $guardian = Guardian::updateOrCreate(
            [
                'school_id' => $school->id,
                'email' => $email,
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'relationship' => $relationship,
                'address' => $student->address ?? 'Harare, Zimbabwe',
                'is_primary' => $isPrimary,
                'can_receive_notifications' => true,
            ]
        );

        DB::table('guardian_student')->updateOrInsert(
            [
                'guardian_id' => $guardian->id,
                'student_id' => $student->id,
            ],
            [
                'relationship' => $relationship,
                'is_primary' => $isPrimary,
                'can_pickup' => true,
                'emergency_contact' => $isPrimary,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($isPrimary) {
            $student->forceFill([
                'guardian_first_name' => $firstName,
                'guardian_last_name' => $lastName,
                'guardian_phone' => $phone,
                'guardian_email' => $email,
                'guardian_relationship' => $relationship,
            ])->save();
        }

        return $guardian;
    }

    public static function linkDemoParentAccount(School $school): void
    {
        $parentUser = User::query()
            ->where('email', 'parent@school.co.zw')
            ->where('school_id', $school->id)
            ->first();

        if (!$parentUser) {
            return;
        }

        $student = Student::query()
            ->where('school_id', $school->id)
            ->where(function ($query): void {
                $query
                    ->where(function ($nameQuery): void {
                        $nameQuery->where('first_name', 'Nyasha')
                            ->where('last_name', 'Chiremba');
                    })
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'student@school.co.zw'));
            })
            ->first();

        if (!$student) {
            return;
        }

        self::linkGuardianToStudent(
            school: $school,
            student: $student,
            firstName: $parentUser->first_name ?? 'Tatenda',
            lastName: $parentUser->last_name ?? 'Moyo',
            phone: $parentUser->phone ?? ZimbabweData::phone(),
            relationship: 'parent',
            isPrimary: true,
            email: 'parent@school.co.zw',
        );

        DB::table('parent_student')->updateOrInsert(
            [
                'parent_id' => $parentUser->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $school->id,
                'relationship' => 'parent',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
