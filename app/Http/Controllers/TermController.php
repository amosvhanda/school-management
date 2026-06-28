<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TermController extends Controller
{
    /**
     * Get all terms for the school
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $query = Term::where('school_id', $schoolId);

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $terms = $query->orderBy('academic_year', 'desc')
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => $terms,
        ]);
    }

    /**
     * Get current term
     */
    public function current(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $term = Term::current($schoolId)->first();

        if (!$term) {
            return response()->json([
                'message' => 'No current term found',
            ], 404);
        }

        return response()->json([
            'data' => $term,
        ]);
    }

    /**
     * Get a specific term
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $term = Term::where('school_id', $schoolId)
            ->findOrFail($id);

        return response()->json([
            'data' => $term,
        ]);
    }

    /**
     * Create a new term
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string|max:9',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'order' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_current' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If setting as current, unset other current terms in the same academic year
        if ($request->boolean('is_current')) {
            Term::where('school_id', $schoolId)
                ->where('academic_year', $request->academic_year)
                ->update(['is_current' => false]);
        }

        $term = Term::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'academic_year' => $request->academic_year,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'order' => $request->order ?? 1,
            'description' => $request->description,
            'is_current' => $request->boolean('is_current', false),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Term created successfully',
            'data' => $term,
        ], 201);
    }

    /**
     * Update a term
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $term = Term::where('school_id', $schoolId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'academic_year' => 'sometimes|string|max:9',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'order' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_current' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If setting as current, unset other current terms in the same academic year
        if ($request->has('is_current') && $request->boolean('is_current')) {
            Term::where('school_id', $schoolId)
                ->where('academic_year', $term->academic_year)
                ->where('id', '!=', $term->id)
                ->update(['is_current' => false]);
        }

        $term->update($request->only([
            'name',
            'academic_year',
            'start_date',
            'end_date',
            'order',
            'description',
            'is_current',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Term updated successfully',
            'data' => $term->fresh(),
        ]);
    }

    /**
     * Delete a term
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $term = Term::where('school_id', $schoolId)
            ->findOrFail($id);

        // Check if term has exams or other data
        if ($term->exams()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete term with associated exams',
            ], 422);
        }

        $term->delete();

        return response()->json([
            'message' => 'Term deleted successfully',
        ]);
    }
}
