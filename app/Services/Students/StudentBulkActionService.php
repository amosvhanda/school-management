<?php

namespace App\Services\Students;

use App\Models\Setting;
use App\Models\Student;
use App\Services\StudentPromotionService;
use Illuminate\Database\Eloquent\Builder;

class StudentBulkActionService
{
    public function __construct(
        private StudentPromotionService $promotionService,
    ) {}

    /**
     * @param  Builder<Student>  $schoolScopedQuery
     * @param  list<int>  $studentIds
     */
    public function updateStatus(Builder $schoolScopedQuery, array $studentIds, string $status): int
    {
        return $schoolScopedQuery
            ->whereIn('id', $studentIds)
            ->update(['status' => $status]);
    }

    /**
     * @param  list<int>|null  $studentIds  Null = whole school year-end
     * @return array{promoted: int, repeated: int, graduated: int, errors: array<int, string>}
     */
    public function promote(
        int $schoolId,
        ?array $studentIds,
        ?int $processedBy,
        ?string $academicYear = null,
        ?string $nextAcademicYear = null,
        float $passMark = 50.0,
    ): array {
        if ($studentIds !== null) {
            $studentIds = Student::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $studentIds)
                ->pluck('id')
                ->values()
                ->all();

            if ($studentIds === []) {
                throw new \InvalidArgumentException('No valid students were found for this school.');
            }
        }

        $academicYear ??= Setting::get(
            'academic.academicYear',
            (string) now()->year
        );

        $nextAcademicYear ??= (string) ((int) $academicYear + 1);

        return $this->promotionService->runYearEnd(
            schoolId: $schoolId,
            academicYear: $academicYear,
            nextAcademicYear: $nextAcademicYear,
            passMark: $passMark,
            studentIds: $studentIds,
            processedBy: $processedBy,
        );
    }
}
