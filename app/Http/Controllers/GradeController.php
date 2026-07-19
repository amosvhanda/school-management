<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\School;
use App\Models\GradeLevel;
use App\Models\Assignment;
use App\Models\Test;
use App\Models\Subject;
use App\Services\SchoolConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradeController extends Controller
{
    protected SchoolConfigurationService $configService;

    public function __construct(SchoolConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    public function getByClass($classId, Request $request)
    {
        $schoolId = $request->user()?->school_id;
        // Handle both class name and class ID
        $query = Grade::with(['student', 'teacher'])
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        
        // Check if classId is numeric (ID) or string (class name)
        if (is_numeric($classId)) {
            $class = \App\Models\ClassModel::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->find($classId);
            if ($class) {
                $query->where(function ($q) use ($class) {
                    $q->where('class_id', $class->id)
                        ->orWhereHas('student', function ($sq) use ($class) {
                            $sq->where('class_id', $class->id)
                                ->orWhere('class', $class->name);
                        });
                });
            } else {
                $query->where('class_id', $classId);
            }
        } else {
            // If it's a class name, search directly
            $query->whereHas('student', function($q) use ($classId) {
                $q->where('class', $classId);
            });
        }

        if ($request->has('term')) {
            $query->where('term', $request->term);
        }
        if ($request->has('subject')) {
            $query->where('subject', $request->subject);
        }

        $grades = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $grades,
        ]);
    }

    public function getByStudent(Request $request, $studentId)
    {
        $schoolId = $request->user()?->school_id;
        $grades = Grade::with(['classModel', 'teacher'])
            ->where('student_id', $studentId)
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $grades,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $school = School::findOrFail($schoolId);

        $request->merge([
            'assessment_type' => $request->input('assessment_type', 'other'),
        ]);

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'exists:students,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'score' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'term' => ['nullable', 'string'],
            'year' => ['nullable', 'integer'],
            'assessment_type' => ['required', 'string', 'in:test,assignment,exam,project,quiz,other'],
            'grade_level_id' => ['nullable', 'exists:grade_levels,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'test_id' => ['required_if:assessment_type,test', 'nullable', 'exists:tests,id'],
            'assignment_id' => ['required_if:assessment_type,assignment', 'nullable', 'exists:assignments,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate student belongs to school
        $student = Student::where('school_id', $schoolId)
            ->findOrFail($request->student_id);

        // Validate class belongs to school and matches student
        $class = ClassModel::where('school_id', $schoolId)->findOrFail($request->class_id);
        if ($student->class_id && (int) $student->class_id !== (int) $class->id) {
            return response()->json([
                'message' => 'Student does not belong to the selected class',
                'errors' => ['class_id' => ['Student is assigned to a different class']],
            ], 422);
        }
        if (!$student->class_id && $student->class && $student->class !== $class->name) {
            return response()->json([
                'message' => 'Student does not belong to the selected class',
                'errors' => ['class_id' => ['Student is assigned to a different class']],
            ], 422);
        }

        // Validate subject belongs to school
        $subject = Subject::where('school_id', $schoolId)->findOrFail($request->subject_id);

        // Validate grade level if provided
        if ($request->has('grade_level_id')) {
            $gradeLevel = GradeLevel::where('school_id', $schoolId)
                ->where('id', $request->grade_level_id)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Validate student's grade level matches
            if ($student->grade_level_id && $student->grade_level_id != $request->grade_level_id) {
                return response()->json([
                    'message' => 'Student grade level does not match provided grade level',
                    'errors' => ['grade_level_id' => ['Student is in a different grade level']],
                ], 422);
            }
        } else {
            // Use student's current grade level when available
            if ($student->grade_level_id) {
                $request->merge(['grade_level_id' => $student->grade_level_id]);
            }
        }

        $teacherId = $request->teacher_id;
        $test = null;
        $assignment = null;

        if ($request->filled('test_id')) {
            $test = Test::where('school_id', $schoolId)
                ->where('class_id', $request->class_id)
                ->where('subject_id', $request->subject_id)
                ->findOrFail($request->test_id);
            $teacherId = $test->teacher_id;
        }

        if ($request->filled('assignment_id')) {
            $assignment = Assignment::where('school_id', $schoolId)
                ->where('class_id', $request->class_id)
                ->findOrFail($request->assignment_id);
            if ($assignment->subject && $assignment->subject !== $subject->name) {
                return response()->json([
                    'message' => 'Assignment subject does not match selected subject',
                    'errors' => ['assignment_id' => ['Assignment subject mismatch']],
                ], 422);
            }
            $teacherId = $assignment->teacher_id;
        }

        app(\App\Services\Domain\SchoolDomainRules::class)->assertMarksWithinMaximum(
            (float) $request->score,
            (float) $request->total,
        );

        // Calculate percentage and get grade from school's grading scale
        $percentage = ($request->score / $request->total) * 100;
        $grade = $request->input('grade') ?: $this->configService->getGradeForScore($school, $percentage);

        // Get grading scale entry
        $gradingScale = \App\Models\GradingScale::where('school_id', $schoolId)
            ->where('grade', $grade)
            ->first();

        $gradeRecord = Grade::create([
            'student_id' => $request->student_id,
            'test_id' => $request->test_id,
            'assignment_id' => $request->assignment_id,
            'subject_id' => $request->subject_id,
            'subject' => $subject->name,
            'assessment_type' => $request->assessment_type,
            'score' => $request->score,
            'total' => $request->total,
            'grade' => $grade,
            'term' => $request->term,
            'year' => $request->year ?? date('Y'),
            'grade_level_id' => $request->grade_level_id,
            'class_id' => $request->class_id,
            'teacher_id' => $teacherId,
            'grading_scale_id' => $gradingScale?->id,
            'school_id' => $schoolId,
        ]);

        return response()->json([
            'data' => $gradeRecord->load(['student', 'gradeLevel', 'gradingScale', 'subject', 'test', 'assignment']),
            'message' => 'Grade recorded successfully',
        ], 201);
    }

    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|file|mimes:csv,txt',
            'grades' => 'nullable|array',
            'class' => 'nullable|string',
            'class_id' => 'nullable|integer',
            'term' => 'nullable|string',
            'year' => 'nullable|integer',
            'assessment_type' => 'nullable|string',
            'teacher_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->hasFile('file') && !$request->filled('grades')) {
            return response()->json([
                'message' => 'No grades provided',
                'errors' => ['grades' => ['Provide a CSV file or grades array']],
            ], 422);
        }

        $schoolId = $request->user()->school_id;
        $school = School::findOrFail($schoolId);
        $defaultClass = null;

        if ($request->filled('class_id')) {
            $defaultClass = ClassModel::where('school_id', $schoolId)->find($request->class_id);
        } elseif ($request->filled('class')) {
            $defaultClass = ClassModel::where('school_id', $schoolId)
                ->where('name', $request->class)
                ->first();
        }

        $headerMap = [
            'student' => 'student_name',
            'student_name' => 'student_name',
            'student_full_name' => 'student_name',
            'full_name' => 'student_name',
            'name' => 'student_name',
            'student_id' => 'student_id',
            'subject' => 'subject',
            'subject_name' => 'subject',
            'subject_id' => 'subject_id',
            'assessment' => 'assessment_type',
            'assessment_type' => 'assessment_type',
            'score' => 'score',
            'marks' => 'score',
            'total' => 'total',
            'max_score' => 'total',
            'max_marks' => 'total',
            'term' => 'term',
            'year' => 'year',
            'class' => 'class',
            'class_id' => 'class_id',
            'test_id' => 'test_id',
            'test_name' => 'test_name',
            'test_title' => 'test_name',
            'assignment_id' => 'assignment_id',
            'assignment_name' => 'assignment_title',
            'assignment_title' => 'assignment_title',
            'teacher_id' => 'teacher_id',
            'grade_level_id' => 'grade_level_id',
        ];

        $rows = [];
        if ($request->hasFile('file')) {
            $handle = fopen($request->file('file')->getRealPath(), 'r');
            if ($handle !== false) {
                $headers = null;
                $line = 0;
                $defaultHeaders = ['student_name', 'subject', 'assessment_type', 'score', 'total', 'term', 'year'];
                while (($data = fgetcsv($handle)) !== false) {
                    $line++;
                    $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $data);
                    $isEmpty = count(array_filter($data, fn ($value) => $value !== null && $value !== '')) === 0;
                    if ($isEmpty) {
                        continue;
                    }

                    if (!$headers) {
                        $normalizedHeaders = array_map(function ($header) {
                            $header = strtolower(trim((string) $header));
                            $header = str_replace([' ', '-'], '_', $header);
                            return $header;
                        }, $data);

                        $hasKnownHeader = count(array_intersect($normalizedHeaders, array_keys($headerMap))) > 0;
                        if ($hasKnownHeader) {
                            $headers = $normalizedHeaders;
                            continue;
                        }

                        $headers = $defaultHeaders;
                    }

                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        if (!array_key_exists($index, $data)) {
                            continue;
                        }
                        $rowData[$header] = $data[$index];
                    }

                    $rows[] = [
                        'line' => $line,
                        'data' => $rowData,
                    ];
                }
                fclose($handle);
            }
        } else {
            foreach ((array) $request->input('grades', []) as $index => $row) {
                $rows[] = [
                    'line' => $index + 1,
                    'data' => $row,
                ];
            }
        }

        $normalizeRow = function ($row) use ($headerMap) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $key = strtolower(trim((string) $key));
                $key = str_replace([' ', '-'], '_', $key);
                $mappedKey = $headerMap[$key] ?? null;
                if (!$mappedKey) {
                    continue;
                }
                $normalized[$mappedKey] = is_string($value) ? trim($value) : $value;
            }
            return $normalized;
        };

        $created = [];
        $errors = [];
        $allowedAssessmentTypes = ['test', 'assignment', 'exam', 'project', 'quiz', 'other'];

        foreach ($rows as $rowInfo) {
            $line = $rowInfo['line'];
            $raw = $normalizeRow($rowInfo['data']);

            $rowErrors = [];
            $assessmentType = strtolower((string) ($raw['assessment_type'] ?? $request->assessment_type ?? ''));
            if (!$assessmentType) {
                if (!empty($raw['test_id']) || !empty($raw['test_name'])) {
                    $assessmentType = 'test';
                } elseif (!empty($raw['assignment_id']) || !empty($raw['assignment_title'])) {
                    $assessmentType = 'assignment';
                }
            }
            if (!$assessmentType || !in_array($assessmentType, $allowedAssessmentTypes, true)) {
                $rowErrors[] = 'Invalid assessment type.';
            }

            $rowClass = null;
            if (!empty($raw['class_id'])) {
                $rowClass = ClassModel::where('school_id', $schoolId)->find($raw['class_id']);
            } elseif (!empty($raw['class'])) {
                $rowClass = ClassModel::where('school_id', $schoolId)
                    ->where('name', $raw['class'])
                    ->first();
            } else {
                $rowClass = $defaultClass;
            }

            if (!$rowClass) {
                $rowErrors[] = 'Class not found or not provided.';
            }

            $student = null;
            if (!empty($raw['student_id'])) {
                $student = Student::where('school_id', $schoolId)->find($raw['student_id']);
            } elseif (!empty($raw['student_name'])) {
                $studentName = $raw['student_name'];
                $student = Student::where('school_id', $schoolId)
                    ->where(function ($q) use ($studentName) {
                        $q->where('full_name', $studentName)
                          ->orWhereRaw("TRIM(CONCAT(first_name, ' ', last_name)) = ?", [$studentName]);
                    })
                    ->first();
            }

            if (!$student) {
                $rowErrors[] = 'Student not found.';
            }

            $subject = null;
            if (!empty($raw['subject_id'])) {
                $subject = Subject::where('school_id', $schoolId)->find($raw['subject_id']);
            } elseif (!empty($raw['subject'])) {
                $subjectValue = $raw['subject'];
                if (is_numeric($subjectValue)) {
                    $subject = Subject::where('school_id', $schoolId)->find((int) $subjectValue);
                }
                if (!$subject) {
                    $subject = Subject::where('school_id', $schoolId)
                        ->where('name', $subjectValue)
                        ->first();
                }
            }

            if (!$subject) {
                $rowErrors[] = 'Subject not found.';
            }

            $score = isset($raw['score']) ? (float) $raw['score'] : null;
            $total = isset($raw['total']) ? (float) $raw['total'] : null;
            if ($score === null || $total === null || $total <= 0) {
                $rowErrors[] = 'Score and total are required.';
            }

            if ($student && $rowClass) {
                if ($student->class_id && (int) $student->class_id !== (int) $rowClass->id) {
                    $rowErrors[] = 'Student does not belong to the selected class.';
                }
                if (!$student->class_id && $student->class && $student->class !== $rowClass->name) {
                    $rowErrors[] = 'Student does not belong to the selected class.';
                }
            }

            $gradeLevelId = $raw['grade_level_id'] ?? $request->grade_level_id;
            if ($gradeLevelId) {
                $gradeLevel = GradeLevel::where('school_id', $schoolId)
                    ->where('id', $gradeLevelId)
                    ->where('is_active', true)
                    ->first();
                if (!$gradeLevel) {
                    $rowErrors[] = 'Grade level not found or inactive.';
                } elseif ($student && $student->grade_level_id && (int) $student->grade_level_id !== (int) $gradeLevel->id) {
                    $rowErrors[] = 'Student grade level does not match provided grade level.';
                }
            } elseif ($student && !$student->grade_level_id) {
                $rowErrors[] = 'Student must have a grade level assigned.';
            }

            $teacherId = $raw['teacher_id'] ?? $request->teacher_id;
            $test = null;
            $assignment = null;

            if ($assessmentType === 'test') {
                if (empty($raw['test_id']) && empty($raw['test_name'])) {
                    $rowErrors[] = 'Test ID or name is required for assessment type test.';
                }
            }

            if ($assessmentType === 'assignment') {
                if (empty($raw['assignment_id']) && empty($raw['assignment_title'])) {
                    $rowErrors[] = 'Assignment ID or title is required for assessment type assignment.';
                }
            }

            if (!empty($raw['test_id']) && $rowClass && $subject) {
                $test = Test::where('school_id', $schoolId)
                    ->where('class_id', $rowClass->id)
                    ->where('subject_id', $subject->id)
                    ->find($raw['test_id']);
                if (!$test) {
                    $rowErrors[] = 'Test not found for the selected class and subject.';
                } else {
                    $teacherId = $test->teacher_id;
                }
            } elseif (!empty($raw['test_name']) && $rowClass && $subject) {
                $testName = $raw['test_name'];
                $testQuery = Test::where('school_id', $schoolId)
                    ->where('class_id', $rowClass->id)
                    ->where('subject_id', $subject->id)
                    ->where('name', $testName);
                $test = $testQuery->first()
                    ?? Test::where('school_id', $schoolId)
                        ->where('class_id', $rowClass->id)
                        ->where('subject_id', $subject->id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($testName)])
                        ->first();
                if (!$test) {
                    $rowErrors[] = 'Test not found for the selected class and subject.';
                } else {
                    $teacherId = $test->teacher_id;
                }
            }

            if (!empty($raw['assignment_id']) && $rowClass) {
                $assignment = Assignment::where('school_id', $schoolId)
                    ->where('class_id', $rowClass->id)
                    ->find($raw['assignment_id']);
                if (!$assignment) {
                    $rowErrors[] = 'Assignment not found for the selected class.';
                } elseif ($subject && $assignment->subject && $assignment->subject !== $subject->name) {
                    $rowErrors[] = 'Assignment subject does not match selected subject.';
                } else {
                    $teacherId = $assignment->teacher_id;
                }
            } elseif (!empty($raw['assignment_title']) && $rowClass) {
                $assignmentTitle = $raw['assignment_title'];
                $assignmentQuery = Assignment::where('school_id', $schoolId)
                    ->where('class_id', $rowClass->id)
                    ->where('title', $assignmentTitle);
                $assignment = $assignmentQuery->first()
                    ?? Assignment::where('school_id', $schoolId)
                        ->where('class_id', $rowClass->id)
                        ->whereRaw('LOWER(title) = ?', [strtolower($assignmentTitle)])
                        ->first();
                if (!$assignment) {
                    $rowErrors[] = 'Assignment not found for the selected class.';
                } elseif ($subject && $assignment->subject && $assignment->subject !== $subject->name) {
                    $rowErrors[] = 'Assignment subject does not match selected subject.';
                } else {
                    $teacherId = $assignment->teacher_id;
                }
            }

            if ($rowErrors) {
                $errors[] = [
                    'line' => $line,
                    'errors' => $rowErrors,
                ];
                continue;
            }

            $percentage = ($score / $total) * 100;
            $grade = $this->configService->getGradeForScore($school, $percentage);
            $gradingScale = \App\Models\GradingScale::where('school_id', $schoolId)
                ->where('grade', $grade)
                ->first();

            $created[] = Grade::create([
                'student_id' => $student->id,
                'test_id' => $test?->id,
                'assignment_id' => $assignment?->id,
                'subject_id' => $subject?->id,
                'subject' => $subject?->name,
                'assessment_type' => $assessmentType,
                'score' => $score,
                'total' => $total,
                'grade' => $grade,
                'term' => $raw['term'] ?? $request->term,
                'year' => $raw['year'] ?? $request->year ?? date('Y'),
                'grade_level_id' => $gradeLevelId ?? $student->grade_level_id,
                'class_id' => $rowClass->id,
                'teacher_id' => $teacherId,
                'grading_scale_id' => $gradingScale?->id,
                'school_id' => $schoolId,
            ]);
        }

        return response()->json([
            'data' => [
                'created' => count($created),
                'errors' => $errors,
            ],
            'message' => count($errors) > 0 ? 'Bulk upload completed with some errors' : 'Bulk upload completed successfully',
        ]);
    }

    public function performance(Request $request, $classId)
    {
        // Handle both class name and class ID
        $schoolId = $request->user()?->school_id;
        $query = Grade::with('student')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        
        if (is_numeric($classId)) {
            $class = \App\Models\ClassModel::find($classId);
            if ($class) {
                $className = $class->name;
            } else {
                $className = $classId;
            }
        } else {
            $className = $classId;
        }
        
        $grades = $query->whereHas('student', function($q) use ($className) {
                $q->where('class', $className);
            })
            ->get();

        // Calculate performance metrics
        $averageScore = $grades->avg('score');
        $subjectAverages = $grades->groupBy('subject')->map(function($subjectGrades) {
            return $subjectGrades->avg('score');
        });

        return response()->json([
            'data' => [
                'average_score' => round($averageScore, 2),
                'subject_averages' => $subjectAverages->map(function($avg) {
                    return round($avg, 2);
                }),
                'total_students' => $grades->pluck('student_id')->unique()->count(),
                'total_grades' => $grades->count(),
            ],
        ]);
    }

}
