<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPaginatedList;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    use RespondsWithPaginatedList;

    public function index(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $query = ClassModel::query()
            ->when($schoolId, fn ($builder) => $builder->where('school_id', $schoolId));

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('form')) {
            $query->where('form', $request->form);
        }

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->integer('grade_level_id'));
        }

        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->integer('stream_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('form', 'like', "%{$search}%");
            });
        }

        $query->with(['teacher', 'gradeLevel', 'stream'])->orderBy('name');

        return $this->indexResponse($request, $query);
    }

    public function show(Request $request, ClassModel $class)
    {
        if ($request->user()?->school_id && $class->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $class->load(['teacher', 'students', 'gradeLevel', 'stream']);

        return response()->json([
            'data' => $class,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'form' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'grade_level_id' => [
                'nullable',
                Rule::exists('grade_levels', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'stream_id' => [
                'nullable',
                Rule::exists('streams', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = ClassModel::create([
            'name' => $request->name,
            'form' => $request->form,
            'capacity' => $request->capacity ?? 40,
            'status' => 'active',
            'grade_level_id' => $request->grade_level_id,
            'stream_id' => $request->stream_id,
            'teacher_id' => $request->teacher_id,
            'school_id' => $schoolId,
        ]);

        return response()->json([
            'data' => $class,
            'message' => 'Class created successfully',
        ], 201);
    }

    public function update(Request $request, ClassModel $class)
    {
        if ($request->user()?->school_id && $class->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $schoolId = $request->user()?->school_id;

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'form' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'grade_level_id' => [
                'nullable',
                Rule::exists('grade_levels', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'stream_id' => [
                'nullable',
                Rule::exists('streams', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class->fill($request->only(['name', 'form', 'capacity', 'grade_level_id', 'stream_id', 'teacher_id']));
        $class->save();

        return response()->json([
            'data' => $class,
            'message' => 'Class updated successfully',
        ]);
    }

    public function destroy(Request $request, ClassModel $class)
    {
        if ($request->user()?->school_id && $class->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully',
        ]);
    }
}
