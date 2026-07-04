<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Teacher\StoreTeacherRequest;
use App\Http\Requests\Api\V1\Teacher\UpdateTeacherRequest;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\CustomField;
use App\Models\Teacher;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function __construct(private CustomFieldService $customFieldService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        $query = Teacher::query();

        if ($request->user()?->school_id) {
            $query->where('school_id', $request->user()->school_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $teachers = $request->boolean('all')
            ? $query->orderBy('name')->get()
            : $query->orderBy('name')->limit($request->integer('limit', 50))->get();

        return $this->success(TeacherResource::collection($teachers));
    }

    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);

        return $this->success(new TeacherResource($teacher));
    }

    public function store(StoreTeacherRequest $request)
    {
        $firstName = $request->firstName ?? explode(' ', $request->name)[0] ?? '';
        $surname = $request->surname ?? (count(explode(' ', $request->name ?? '') ?? []) > 1
            ? implode(' ', array_slice(explode(' ', $request->name), 1))
            : '');

        $teacher = Teacher::create([
            'name' => trim($firstName.' '.$surname) ?: $request->name,
            'first_name' => $firstName,
            'last_name' => $surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'subject' => $request->subject,
            'department' => $request->department,
            'qualification' => $request->qualification,
            'joining_date' => $request->joiningDate,
            'employee_id' => 'TCH'.date('Y').str_pad(Teacher::count() + 1, 4, '0', STR_PAD_LEFT),
            'status' => 'active',
            'school_id' => $request->user()->school_id,
        ]);

        if ($request->filled('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $teacher,
                CustomField::ENTITY_TEACHER,
                $request->input('custom_fields', [])
            );
        }

        return $this->created(new TeacherResource($teacher->fresh()), 'Teacher created successfully');
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        if ($request->has('firstName') || $request->has('surname')) {
            $teacher->first_name = $request->firstName ?? $teacher->first_name;
            $teacher->last_name = $request->surname ?? $teacher->last_name;
            $teacher->name = trim($teacher->first_name.' '.$teacher->last_name);
        }

        $teacher->fill($request->only(['email', 'phone', 'address', 'subject', 'department', 'qualification', 'status']));
        if ($request->has('joiningDate')) {
            $teacher->joining_date = $request->joiningDate;
        }
        $teacher->save();

        if ($request->has('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $teacher,
                CustomField::ENTITY_TEACHER,
                $request->input('custom_fields', [])
            );
        }

        return $this->success(new TeacherResource($teacher->fresh()), 'Teacher updated successfully');
    }

    public function destroy(Teacher $teacher)
    {
        $this->authorize('delete', $teacher);
        $teacher->delete();

        return $this->success(message: 'Teacher deleted successfully');
    }
}
