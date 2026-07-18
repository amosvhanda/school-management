<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->user()?->school_id;

        $query = ClassModel::query()
            ->when($schoolId, fn ($builder) => $builder->where('school_id', $schoolId));

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('form')) {
            $query->where('form', $request->form);
        }

        $query->with('teacher');

        // Support 'all=true' parameter to get all classes without pagination
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            $classes = $query->orderBy('name')->get();
        } else {
            // Default pagination or limit
            $limit = $request->get('limit', 50);
            $classes = $query->orderBy('name')->limit($limit)->get();
        }

        return response()->json([
            'data' => $classes,
        ]);
    }

    public function show(Request $request, ClassModel $class)
    {
        if ($request->user()?->school_id && $class->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $class->load(['teacher', 'students']);

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

        $class->fill($request->only(['name', 'form', 'capacity', 'teacher_id']));
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
