<?php

namespace App\Http\Controllers;

use App\Models\GradeLevelSubject;
use App\Models\House;
use App\Models\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AcademicStructureController extends Controller
{
    public function streams(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $query = Stream::query()->where('school_id', $schoolId);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function storeStream(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ])->validate();

        $stream = Stream::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['message' => 'Stream created', 'data' => $stream], 201);
    }

    public function updateStream(Request $request, int $id)
    {
        $stream = Stream::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($id);

        $data = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ])->validate();

        $stream->update($data);

        return response()->json(['message' => 'Stream updated', 'data' => $stream->fresh()]);
    }

    public function houses(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $query = House::query()
            ->where('school_id', $schoolId)
            ->with('teacher:id,first_name,last_name,full_name');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function storeHouse(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:30',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'is_active' => 'nullable|boolean',
        ])->validate();

        $house = House::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['message' => 'House created', 'data' => $house], 201);
    }

    public function updateHouse(Request $request, int $id)
    {
        $house = House::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($id);

        $data = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:30',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'is_active' => 'nullable|boolean',
        ])->validate();

        $house->update($data);

        return response()->json(['message' => 'House updated', 'data' => $house->fresh()]);
    }

    public function subjectPackages(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $query = GradeLevelSubject::query()
            ->where('school_id', $schoolId)
            ->with(['gradeLevel:id,name', 'subject:id,name,code', 'stream:id,name']);

        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->integer('grade_level_id'));
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->integer('stream_id'));
        }

        return response()->json(['data' => $query->orderBy('grade_level_id')->get()]);
    }

    public function storeSubjectPackage(Request $request)
    {
        $data = Validator::make($request->all(), [
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'stream_id' => 'nullable|integer|exists:streams,id',
            'is_core' => 'nullable|boolean',
        ])->validate();

        $isCore = $data['is_core'] ?? true;
        if (is_string($isCore)) {
            $isCore = ! in_array(strtolower($isCore), ['false', '0', 'elective', 'no'], true);
        }

        $row = GradeLevelSubject::updateOrCreate(
            [
                'school_id' => $request->user()->school_id,
                'grade_level_id' => $data['grade_level_id'],
                'subject_id' => $data['subject_id'],
                'stream_id' => $data['stream_id'] ?? null,
            ],
            [
                'is_core' => (bool) $isCore,
            ],
        );

        return response()->json([
            'message' => 'Subject package item saved',
            'data' => $row->load(['gradeLevel:id,name', 'subject:id,name,code', 'stream:id,name']),
        ], 201);
    }
}
