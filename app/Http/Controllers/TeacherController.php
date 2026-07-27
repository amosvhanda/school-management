<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Subject;
use App\Services\StaffNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    public function __construct(private StaffNumberService $staffNumbers) {}
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('subject')) {
            $query->where('subject', $request->subject);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Support 'all=true' parameter to get all teachers without pagination
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            $teachers = $query->orderBy('name')->get();
        } else {
            $limit = $request->get('limit', 50);
            $teachers = $query->orderBy('name')->limit($limit)->get();
        }

        return response()->json([
            'data' => $teachers,
        ]);
    }

    public function show(Request $request, $id)
    {
        $schoolId = $request->user()?->school_id;
        $query = Teacher::query()->with(['designation:id,name,code']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $teacher = $query->findOrFail($id);

        return response()->json([
            'data' => $teacher,
        ]);
    }

    public function store(Request $request)
    {
        // Get valid subjects from database
        $user = $request->user();
        $schoolId = $user->school_id;
        $validSubjects = Subject::where('school_id', $schoolId)
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
            'phone' => 'nullable|string|max:20',
            'subject' => ['nullable', 'string', function ($attribute, $value, $fail) use ($validSubjects) {
                if ($value && !in_array($value, $validSubjects)) {
                    $fail('The selected subject is not valid. Please select from the globally configured subjects.');
                }
            }],
            'department' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = $user->school_id;

        $teacher = Teacher::create([
            'name' => $request->name,
            'first_name' => explode(' ', $request->name)[0] ?? '',
            'last_name' => explode(' ', $request->name)[1] ?? '',
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'department' => $request->department,
            'employee_id' => $this->staffNumbers->generateEmployeeNumber((int) $schoolId),
            'status' => 'active',
            'school_id' => $schoolId,
        ]);

        return response()->json([
            'data' => $teacher,
            'message' => 'Teacher created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        // Get valid subjects from database
        $user = $request->user();
        $schoolId = $user->school_id;
        $validSubjects = Subject::where('school_id', $schoolId)
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:teachers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'subject' => ['nullable', 'string', function ($attribute, $value, $fail) use ($validSubjects) {
                if ($value && !in_array($value, $validSubjects)) {
                    $fail('The selected subject is not valid. Please select from the globally configured subjects.');
                }
            }],
            'department' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teacher->fill($request->only([
            'name', 'email', 'phone', 'subject', 'department',
            'employee_id', 'qualification', 'joining_date', 'status', 'address'
        ]));
        $teacher->save();

        return response()->json([
            'data' => $teacher,
            'message' => 'Teacher updated successfully',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,on_leave,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teacher = Teacher::findOrFail($id);
        $teacher->status = $request->status;
        $teacher->save();

        return response()->json([
            'data' => $teacher,
            'message' => 'Teacher status updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();

        return response()->json([
            'message' => 'Teacher deleted successfully',
        ]);
    }
}
