<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\TeacherAssignment;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TimetableGeneratorService
{
    private const DEFAULT_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    /** Default Zimbabwe-style lesson periods (start, end). */
    private const DEFAULT_PERIODS = [
        ['07:30', '08:15'],
        ['08:15', '09:00'],
        ['09:15', '10:00'],
        ['10:00', '10:45'],
        ['11:00', '11:45'],
        ['11:45', '12:30'],
    ];

    public function __construct(private TimetableConflictService $conflictService) {}

    /**
     * Generate a weekly timetable from active teacher assignments for a class.
     *
     * Builds a full day×period grid, rotating through subject teachers assigned
     * to the class (or its grade level).
     *
     * @return array{created: int, skipped: int, entries: Collection<int, Timetable>, conflicts: array<int, mixed>}
     */
    public function generateForClass(
        int $schoolId,
        int $classId,
        array $days = self::DEFAULT_DAYS,
        string $dayStart = '07:30',
        int $periodMinutes = 45,
        bool $replaceExisting = false,
        ?int $periodsPerDay = null,
    ): array {
        $class = ClassModel::query()
            ->where('school_id', $schoolId)
            ->findOrFail($classId);

        $assignments = $this->resolveAssignmentsForClass($schoolId, $class);

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'class_id' => ['No active teacher assignments found for this class. Assign teachers to subjects first.'],
            ]);
        }

        if ($replaceExisting) {
            Timetable::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $class->id)
                ->delete();
        }

        $periods = $this->buildPeriodSlots($dayStart, $periodMinutes, $periodsPerDay);
        $created = collect();
        $conflicts = [];
        $skipped = 0;
        $slotIndex = 0;

        foreach ($days as $day) {
            foreach ($periods as $period) {
                $placed = false;

                for ($attempt = 0; $attempt < $assignments->count(); $attempt++) {
                    /** @var TeacherAssignment $assignment */
                    $assignment = $assignments[($slotIndex + $attempt) % $assignments->count()];

                    $entry = [
                        'school_id' => $schoolId,
                        'class_id' => $class->id,
                        'teacher_id' => $assignment->teacher_id,
                        'subject_id' => $assignment->subject_id,
                        'subject' => $assignment->subject?->name ?? 'Subject',
                        'day' => $day,
                        'start_time' => $period['start'],
                        'end_time' => $period['end'],
                        'room_id' => $class->room_id,
                    ];

                    $validation = $this->conflictService->validateTimetableEntry($entry);

                    if (! $validation['valid']) {
                        if ($attempt === $assignments->count() - 1) {
                            $conflicts[] = [
                                'assignment_id' => $assignment->id,
                                'subject' => $entry['subject'],
                                'day' => $day,
                                'start_time' => $period['start'],
                                'conflicts' => $validation['conflicts'],
                            ];
                        }

                        continue;
                    }

                    $created->push(Timetable::create($entry));
                    $placed = true;
                    break;
                }

                if (! $placed) {
                    $skipped++;
                }

                $slotIndex++;
            }
        }

        return [
            'created' => $created->count(),
            'skipped' => $skipped,
            'entries' => $created,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Generate timetables for multiple classes (whole school or filtered subset).
     *
     * @return array{
     *     classes_processed: int,
     *     created: int,
     *     skipped: int,
     *     conflicts: array<int, mixed>,
     *     classes: array<int, array<string, mixed>>,
     *     entry_ids: Collection<int, int>
     * }
     */
    public function generateBulk(
        int $schoolId,
        ?array $classIds = null,
        ?int $gradeLevelId = null,
        array $days = self::DEFAULT_DAYS,
        string $dayStart = '07:30',
        int $periodMinutes = 45,
        bool $replaceExisting = false,
    ): array {
        $query = ClassModel::query()->where('school_id', $schoolId);

        if ($classIds !== null && $classIds !== []) {
            $query->whereIn('id', $classIds);
        }

        if ($gradeLevelId !== null) {
            $query->where('grade_level_id', $gradeLevelId);
        }

        $classes = $query->orderBy('name')->get();

        if ($classes->isEmpty()) {
            throw ValidationException::withMessages([
                'class_ids' => ['No classes found for the given criteria. Assign classes or check grade level filter.'],
            ]);
        }

        $classResults = [];
        $totalCreated = 0;
        $totalSkipped = 0;
        $allConflicts = [];
        $allEntryIds = collect();

        foreach ($classes as $class) {
            try {
                $result = $this->generateForClass(
                    schoolId: $schoolId,
                    classId: $class->id,
                    days: $days,
                    dayStart: $dayStart,
                    periodMinutes: $periodMinutes,
                    replaceExisting: $replaceExisting,
                );

                $classResults[] = [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'created' => $result['created'],
                    'skipped' => $result['skipped'],
                    'conflicts' => $result['conflicts'],
                ];

                $totalCreated += $result['created'];
                $totalSkipped += $result['skipped'];
                $allConflicts = array_merge($allConflicts, $result['conflicts']);
                $allEntryIds = $allEntryIds->merge($result['entries']->pluck('id'));
            } catch (ValidationException $exception) {
                $classResults[] = [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'created' => 0,
                    'skipped' => 0,
                    'conflicts' => [],
                    'error' => $exception->errors(),
                ];
            }
        }

        return [
            'classes_processed' => count($classResults),
            'created' => $totalCreated,
            'skipped' => $totalSkipped,
            'conflicts' => $allConflicts,
            'classes' => $classResults,
            'entry_ids' => $allEntryIds,
        ];
    }

    /**
     * Active subject-teacher assignments for a class (class-level preferred over grade-level).
     *
     * @return Collection<int, TeacherAssignment>
     */
    private function resolveAssignmentsForClass(int $schoolId, ClassModel $class): Collection
    {
        return TeacherAssignment::query()
            ->with(['teacher', 'subject'])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereNotNull('subject_id')
            ->whereNotNull('teacher_id')
            ->where(function ($query) use ($class) {
                $query->where('class_id', $class->id);

                if ($class->grade_level_id) {
                    $query->orWhere(function ($gradeQuery) use ($class) {
                        $gradeQuery
                            ->whereNull('class_id')
                            ->where('grade_level_id', $class->grade_level_id);
                    });
                }
            })
            ->get()
            ->sortByDesc(fn (TeacherAssignment $assignment) => $assignment->class_id === $class->id ? 1 : 0)
            ->unique('subject_id')
            ->values();
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    private function buildPeriodSlots(string $dayStart, int $periodMinutes, ?int $periodsPerDay): array
    {
        // Prefer the standard school grid when using the default start.
        if ($dayStart === '07:30' && ($periodsPerDay === null || $periodsPerDay === count(self::DEFAULT_PERIODS))) {
            return array_map(
                fn (array $slot) => ['start' => $slot[0], 'end' => $slot[1]],
                self::DEFAULT_PERIODS,
            );
        }

        $count = $periodsPerDay ?? count(self::DEFAULT_PERIODS);
        $cursor = Carbon::createFromFormat('H:i', $dayStart);
        $periods = [];

        for ($i = 0; $i < $count; $i++) {
            $start = $cursor->format('H:i');
            $end = $cursor->copy()->addMinutes($periodMinutes)->format('H:i');
            $periods[] = ['start' => $start, 'end' => $end];

            $cursor->addMinutes($periodMinutes);

            // Lunch break window
            if ($cursor->format('H:i') >= '13:00' && $cursor->format('H:i') < '14:00') {
                $cursor = Carbon::createFromFormat('H:i', '14:00');
            }
        }

        return $periods;
    }
}
