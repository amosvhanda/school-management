<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\TestResource;
use App\Models\Test;
use App\Models\TestResult;
use App\Models\Term;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    /**
     * Get all tests for the school
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $query = Test::where('school_id', $schoolId)
            ->with(['classModel', 'subject', 'teacher', 'term']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $tests = $query->orderBy('test_date', 'desc')
            ->get();

        return TestResource::collection($tests)
            ->additional(['message' => 'Success']);
    }

    /**
     * Get a specific test
     */
    public function show(Request $request, Test $test)
    {
        $schoolId = $request->user()->school_id;

        $test->load(['classModel', 'subject', 'teacher', 'term', 'testResults.student']);

        // Get all students in the class
        $students = Student::where('school_id', $schoolId)
            ->where('class_id', $test->class_id)
            ->where('status', 'active')
            ->get();

        $test->students = $students;

        return (new TestResource($test))
            ->additional(['message' => 'Success']);
    }

    /**
     * Create a new test
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'term_id' => 'nullable|exists:terms,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'required|numeric|min:0',
            'passing_marks' => 'nullable|numeric|min:0',
            'academic_year' => 'required|string|max:9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate class belongs to school
        $class = ClassModel::where('school_id', $schoolId)
            ->findOrFail($request->class_id);

        // Validate subject belongs to school
        $subject = Subject::where('school_id', $schoolId)
            ->findOrFail($request->subject_id);

        // Validate teacher belongs to school
        $teacher = Teacher::where('school_id', $schoolId)
            ->findOrFail($request->teacher_id);

        // Validate term if provided
        if ($request->has('term_id')) {
            Term::where('school_id', $schoolId)
                ->findOrFail($request->term_id);
        }

        // Validate teacher is assigned to this class
        $teacherAssignment = \App\Models\TeacherAssignment::where('school_id', $schoolId)
            ->where('teacher_id', $request->teacher_id)
            ->where('class_id', $request->class_id)
            ->where('subject_id', $request->subject_id)
            ->first();

        if (!$teacherAssignment) {
            return response()->json([
                'message' => 'Teacher is not assigned to this class and subject',
            ], 422);
        }

        $test = Test::create([
            'school_id' => $schoolId,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'term_id' => $request->term_id,
            'name' => $request->name,
            'description' => $request->description,
            'test_date' => $request->test_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_marks' => $request->total_marks,
            'passing_marks' => $request->passing_marks,
            'academic_year' => $request->academic_year,
            'is_published' => $request->boolean('is_published', false),
        ]);

        return (new TestResource($test->load(['classModel', 'subject', 'teacher', 'term'])))
            ->additional(['message' => 'Test created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a test
     */
    public function update(Request $request, Test $test)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'class_id' => 'sometimes|exists:classes,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'teacher_id' => 'sometimes|exists:teachers,id',
            'term_id' => 'nullable|exists:terms,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'test_date' => 'sometimes|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'sometimes|numeric|min:0',
            'passing_marks' => 'nullable|numeric|min:0',
            'academic_year' => 'sometimes|string|max:9',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate relationships if provided
        if ($request->has('class_id')) {
            ClassModel::where('school_id', $schoolId)
                ->findOrFail($request->class_id);
        }

        if ($request->has('subject_id')) {
            Subject::where('school_id', $schoolId)
                ->findOrFail($request->subject_id);
        }

        if ($request->has('teacher_id')) {
            Teacher::where('school_id', $schoolId)
                ->findOrFail($request->teacher_id);
        }

        if ($request->has('term_id')) {
            Term::where('school_id', $schoolId)
                ->findOrFail($request->term_id);
        }

        $test->update($request->only([
            'class_id',
            'subject_id',
            'teacher_id',
            'term_id',
            'name',
            'description',
            'test_date',
            'start_time',
            'end_time',
            'total_marks',
            'passing_marks',
            'academic_year',
            'is_published',
        ]));

        return (new TestResource($test->fresh()->load(['classModel', 'subject', 'teacher', 'term'])))
            ->additional(['message' => 'Test updated successfully']);
    }

    /**
     * Delete a test
     */
    public function destroy(Request $request, Test $test)
    {
        // Delete test results
        $test->testResults()->delete();

        $test->delete();

        return response()->json([
            'message' => 'Test deleted successfully',
        ]);
    }

    /**
     * Record test results for students
     */
    public function recordResults(Request $request, Test $test)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.student_id' => 'required|exists:students,id',
            'results.*.marks_obtained' => 'required|numeric|min:0',
            'results.*.remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($test, $request, $schoolId) {
            foreach ($request->results as $resultData) {
                // Validate student belongs to school and class
                $student = Student::where('school_id', $schoolId)
                    ->where('class_id', $test->class_id)
                    ->findOrFail($resultData['student_id']);

                TestResult::updateOrCreate(
                    [
                        'test_id' => $test->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'school_id' => $schoolId,
                        'subject_id' => $test->subject_id,
                        'marks_obtained' => $resultData['marks_obtained'],
                        'total_marks' => $test->total_marks,
                        'remarks' => $resultData['remarks'] ?? null,
                    ]
                );
            }
        });

        return (new TestResource($test->fresh()->load('testResults.student')))
            ->additional(['message' => 'Test results recorded successfully']);
    }
}
