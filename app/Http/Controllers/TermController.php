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

        if ($request->has('is_current')) {
            $query->where('is_current', $request->boolean('is_current'));
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
    public function show(Request $request, Term $term)
    {
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

        $makeCurrent = $request->boolean('is_current');

        $term = Term::create([
            'school_id' => $schoolId,
            'name' => $request->name,
            'academic_year' => $request->academic_year,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'order' => $request->order ?? 1,
            'description' => $request->description,
            'is_current' => false,
            'is_active' => $makeCurrent ? true : $request->boolean('is_active', false),
        ]);

        if ($makeCurrent) {
            $this->makeExclusiveCurrent($term);
        }

        return response()->json([
            'message' => 'Term created successfully',
            'data' => $term->fresh(),
        ], 201);
    }

    /**
     * Update a term
     */
    public function update(Request $request, Term $term)
    {
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

        $makeCurrent = $request->has('is_current') && $request->boolean('is_current');

        if ($makeCurrent) {
            // Exclusive current term: clear current/active on every other school term first.
            $this->makeExclusiveCurrent($term, updateAttributes: $request->only([
                'name',
                'academic_year',
                'start_date',
                'end_date',
                'order',
                'description',
            ]));
        } else {
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
        }

        return response()->json([
            'message' => 'Term updated successfully',
            'data' => $term->fresh(),
        ]);
    }

    /**
     * Mark one term as the school's sole current + active term.
     *
     * @param  array<string, mixed>  $updateAttributes
     */
    private function makeExclusiveCurrent(Term $term, array $updateAttributes = []): void
    {
        Term::where('school_id', $term->school_id)
            ->where('id', '!=', $term->id)
            ->update([
                'is_current' => false,
                'is_active' => false,
            ]);

        $term->update([
            ...$updateAttributes,
            'is_current' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Delete a term
     */
    public function destroy(Request $request, Term $term)
    {
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
