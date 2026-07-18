<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /**
     * Get all assignments (scoped to user's school)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;
        $teacherId = $request->get('teacher_id');
        $classId = $request->get('class_id');
        $status = $request->get('status');

        $query = DB::table('assignments')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->join('teachers', 'assignments.teacher_id', '=', 'teachers.id')
            ->select(
                'assignments.*',
                'classes.name as class_name',
                'teachers.name as teacher_name'
            )
            ->orderBy('assignments.due_date', 'asc')
            ->orderBy('assignments.created_at', 'desc');

        if ($schoolId !== null) {
            $query->where('assignments.school_id', $schoolId);
        }

        if ($teacherId) {
            $query->where('assignments.teacher_id', $teacherId);
        }

        if ($classId) {
            $query->where('assignments.class_id', $classId);
        }

        if ($status) {
            $query->where('assignments.status', $status);
        }

        $assignments = $query->get();

        // Get student counts per class (students use 'class' name, not class_id)
        foreach ($assignments as $assignment) {
            $className = $assignment->class_name ?? null;
            $studentCount = 0;
            if ($className) {
                $q = DB::table('students')->where('class', $className)->where('status', 'active');
                if ($schoolId !== null) {
                    $q->where('school_id', $schoolId);
                }
                $studentCount = $q->count();
            }
            $assignment->total_students = $studentCount;
        }

        return response()->json([
            'data' => $assignments,
        ]);
    }

    /**
     * Get a specific assignment (scoped to user's school)
     */
    public function show($id)
    {
        $user = request()->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;

        $query = DB::table('assignments')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->join('teachers', 'assignments.teacher_id', '=', 'teachers.id')
            ->select(
                'assignments.*',
                'classes.name as class_name',
                'teachers.name as teacher_name'
            )
            ->where('assignments.id', $id);
        if ($schoolId !== null) {
            $query->where('assignments.school_id', $schoolId);
        }
        $assignment = $query->first();

        if (! $assignment) {
            return response()->json([
                'message' => 'Assignment not found',
            ], 404);
        }

        return response()->json([
            'data' => $assignment,
        ]);
    }

    /**
     * Create a new assignment
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->school_id === null) {
            return response()->json(['message' => 'User must belong to a school to create assignments.'], 403);
        }

        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subject' => 'required_without:subject_id|string|max:255',
            'subject_id' => [
                'required_without:subject',
                Rule::exists('subjects', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'class_id' => [
                'required',
                Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'due_date' => 'required|date',
            'total_marks' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'instructions' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject = $request->subject;
        if ($request->filled('subject_id')) {
            $subject = DB::table('subjects')->where('id', $request->subject_id)->where('school_id', $schoolId)->value('name')
                ?? $subject;
        }

        $data = [
            'title' => $request->title,
            'subject' => $subject,
            'class_id' => $request->class_id,
            'teacher_id' => $request->teacher_id,
            'due_date' => $request->due_date,
            'description' => $request->description,
            'instructions' => $request->instructions,
            'status' => $request->input('status', 'active'),
            'submissions_count' => 0,
            'total_marks' => $request->input('total_marks', 100),
            'school_id' => $user->school_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $id = DB::table('assignments')->insertGetId($data);

        $assignment = DB::table('assignments')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->join('teachers', 'assignments.teacher_id', '=', 'teachers.id')
            ->select(
                'assignments.*',
                'classes.name as class_name',
                'teachers.name as teacher_name'
            )
            ->where('assignments.id', $id)
            ->first();

        return response()->json([
            'message' => 'Assignment created successfully',
            'data' => $assignment,
        ], 201);
    }

    /**
     * Update an assignment (scoped to user's school)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;
        $q = DB::table('assignments')->where('id', $id);
        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }
        $assignment = $q->first();

        if (! $assignment) {
            return response()->json([
                'message' => 'Assignment not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|required|string|max:255',
            'class_id' => [
                'sometimes',
                'required',
                $schoolId !== null
                    ? Rule::exists('classes', 'id')->where('school_id', $schoolId)
                    : 'exists:classes,id',
            ],
            'teacher_id' => [
                'sometimes',
                'required',
                $schoolId !== null
                    ? Rule::exists('teachers', 'id')->where('school_id', $schoolId)
                    : 'exists:teachers,id',
            ],
            'due_date' => 'sometimes|required|date',
            'total_marks' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:active,completed,cancelled',
            'description' => 'sometimes|string',
            'instructions' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['title', 'subject', 'class_id', 'teacher_id', 'due_date', 'total_marks', 'status', 'description', 'instructions']);
        $data['updated_at'] = now();
        $updateQ = DB::table('assignments')->where('id', $id);
        if ($schoolId !== null) {
            $updateQ->where('school_id', $schoolId);
        }
        $updateQ->update($data);

        $assignment = DB::table('assignments')
            ->join('classes', 'assignments.class_id', '=', 'classes.id')
            ->join('teachers', 'assignments.teacher_id', '=', 'teachers.id')
            ->select(
                'assignments.*',
                'classes.name as class_name',
                'teachers.name as teacher_name'
            )
            ->where('assignments.id', $id)
            ->first();

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => $assignment,
        ]);
    }

    /**
     * Delete an assignment (scoped to user's school)
     */
    public function destroy($id)
    {
        $user = request()->user();
        $schoolId = $user?->isSuperAdmin() ? null : $user?->school_id;
        $q = DB::table('assignments')->where('id', $id);
        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }
        $assignment = $q->first();

        if (! $assignment) {
            return response()->json([
                'message' => 'Assignment not found',
            ], 404);
        }

        $delQ = DB::table('assignments')->where('id', $id);
        if ($schoolId !== null) {
            $delQ->where('school_id', $schoolId);
        }
        $delQ->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully',
        ]);
    }
}
