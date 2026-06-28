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

    public function __construct(private TimetableConflictService $conflictService) {}

    /**
     * Generate a weekly timetable from active teacher assignments for a class.
     *
     * @return array{created: int, skipped: int, entries: Collection<int, Timetable>, conflicts: array<int, mixed>}
     */
    public function generateForClass(
        int $schoolId,
        int $classId,
        array $days = self::DEFAULT_DAYS,
        string $dayStart = '08:00',
        int $periodMinutes = 45,
        bool $replaceExisting = false,
    ): array {
        $class = ClassModel::query()
            ->where('school_id', $schoolId)
            ->findOrFail($classId);

        $assignments = TeacherAssignment::query()
            ->with(['teacher', 'subject'])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($query) use ($class) {
                $query->where('class_id', $class->id);

                if ($class->grade_level_id) {
                    $query->orWhere('grade_level_id', $class->grade_level_id);
                }
            })
            ->whereNotNull('subject_id')
            ->get()
            ->unique(fn (TeacherAssignment $assignment) => $assignment->subject_id.'-'.$assignment->teacher_id);

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

        $created = collect();
        $conflicts = [];
        $skipped = 0;
        $slotCursor = Carbon::createFromFormat('H:i', $dayStart);

        foreach ($assignments->values() as $index => $assignment) {
            $day = $days[$index % count($days)];
            $startTime = $slotCursor->format('H:i');
            $endTime = $slotCursor->copy()->addMinutes($periodMinutes)->format('H:i');

            $entry = [
                'school_id' => $schoolId,
                'class_id' => $class->id,
                'teacher_id' => $assignment->teacher_id,
                'subject_id' => $assignment->subject_id,
                'subject' => $assignment->subject?->name ?? 'Subject',
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room_id' => $class->room_id,
            ];

            $validation = $this->conflictService->validateTimetableEntry($entry);

            if (! $validation['valid']) {
                $conflicts[] = [
                    'assignment_id' => $assignment->id,
                    'subject' => $entry['subject'],
                    'day' => $day,
                    'conflicts' => $validation['conflicts'],
                ];
                $skipped++;

                continue;
            }

            $created->push(Timetable::create($entry));

            $slotCursor->addMinutes($periodMinutes);

            if ($slotCursor->format('H:i') >= '13:00' && $slotCursor->format('H:i') < '14:00') {
                $slotCursor = Carbon::createFromFormat('H:i', '14:00');
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
        string $dayStart = '08:00',
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
}
