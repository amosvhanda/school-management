<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Requests\Api\V1\Teacher\StoreTeacherRequest;
use App\Http\Requests\Api\V1\Teacher\UpdateTeacherRequest;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\CustomField;
use App\Models\Teacher;
use App\Services\CustomFieldService;
use App\Services\StaffNumberService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use HandlesResourceQueries;

    public function __construct(
        private CustomFieldService $customFieldService,
        private StaffNumberService $staffNumbers,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        return $this->paginateResource($request, Teacher::class, TeacherResource::class, [
            'filters' => ['status', 'department', 'subject', 'search'],
            'search_columns' => ['name', 'email', 'employee_id', 'first_name', 'last_name'],
            'sorts' => ['name', 'created_at', 'employee_id', 'status', 'department'],
            'includes' => [],
            'fields' => [
                'teachers.id',
                'teachers.name',
                'teachers.first_name',
                'teachers.last_name',
                'teachers.email',
                'teachers.phone',
                'teachers.employee_id',
                'teachers.department',
                'teachers.subject',
                'teachers.status',
                'teachers.school_id',
                'teachers.created_at',
            ],
            'default_sort' => 'name',
        ]);
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
            'employee_id' => $this->staffNumbers->generateEmployeeNumber((int) $request->user()->school_id),
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
