<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Get all subjects for the school
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $query = Subject::where('school_id', $schoolId);

        // Support 'all=true' parameter (always returns all subjects)
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            // Return all active subjects by default, unless is_active is specified
            if (!$request->has('is_active')) {
                $query->where('is_active', true);
            }
        }

        // Filter by search if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by is_active if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $subjects = $query->orderBy('order')->orderBy('name')->get();

        return response()->json([
            'data' => $subjects,
        ]);
    }

    /**
     * Get a specific subject
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $subject = Subject::where('school_id', $schoolId)
            ->findOrFail($id);

        return response()->json([
            'data' => $subject,
        ]);
    }

    /**
     * Create a new subject
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('subjects')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'class_id' => 'nullable|exists:classes,id',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject = Subject::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'order' => $request->order ?? 0,
            'class_id' => $request->class_id,
            'teacher_id' => $request->teacher_id,
        ]);

        return response()->json([
            'message' => 'Subject created successfully',
            'data' => $subject,
        ], 201);
    }

    /**
     * Update a subject
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $subject = Subject::where('school_id', $schoolId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('subjects')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })->ignore($id)
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('subjects')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })->ignore($id)
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'class_id' => 'nullable|exists:classes,id',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject->update($request->only([
            'name',
            'code',
            'description',
            'is_active',
            'order',
            'class_id',
            'teacher_id',
        ]));

        return response()->json([
            'message' => 'Subject updated successfully',
            'data' => $subject->fresh(),
        ]);
    }

    /**
     * Delete a subject
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $subject = Subject::where('school_id', $schoolId)
            ->findOrFail($id);

        // Check if subject is in use
        if ($subject->grades()->exists() || $subject->timetables()->exists()) {
            return response()->json([
                'message' => 'Cannot delete subject that is in use',
            ], 422);
        }

        $subject->delete();

        return response()->json([
            'message' => 'Subject deleted successfully',
        ]);
    }
}
