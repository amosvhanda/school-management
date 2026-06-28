<?php

namespace App\Http\Controllers;

use App\Models\GradingScale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class GradingScaleController extends Controller
{
    /**
     * Get all grading scales for the school
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        
        $scales = GradingScale::where('school_id', $schoolId)
            ->orderBy('order')
            ->get();

        return response()->json($scales);
    }

    /**
     * Store a new grading scale entry
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;

        $validated = $request->validate([
            'grade' => ['required', 'string', 'max:10', Rule::unique('grading_scales')->where('school_id', $schoolId)],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100', 'gte:min_score'],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $scale = GradingScale::create([
            'school_id' => $schoolId,
            ...$validated,
        ]);

        return response()->json($scale, 201);
    }

    /**
     * Update a grading scale entry
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $scale = GradingScale::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'grade' => ['sometimes', 'string', 'max:10', Rule::unique('grading_scales')->where('school_id', $schoolId)->ignore($id)],
            'min_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['sometimes', 'numeric', 'min:0', 'max:100', 'gte:min_score'],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $scale->update($validated);

        return response()->json($scale);
    }

    /**
     * Delete a grading scale entry
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        $scale = GradingScale::where('school_id', $schoolId)->findOrFail($id);

        // Check if scale is in use
        if ($scale->grades()->exists()) {
            return response()->json([
                'message' => 'Cannot delete grading scale that is in use'
            ], 422);
        }

        $scale->delete();

        return response()->json(['message' => 'Grading scale deleted successfully']);
    }

    /**
     * Get grade for a score
     */
    public function getGradeForScore(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;
        
        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $grade = GradingScale::getGradeForScore($schoolId, $validated['score']);

        return response()->json([
            'score' => $validated['score'],
            'grade' => $grade,
        ]);
    }
}
