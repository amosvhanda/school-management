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
            'includes' => ['designation'],
            'fields' => [
                'teachers.id',
                'teachers.name',
                'teachers.first_name',
                'teachers.last_name',
                'teachers.email',
                'teachers.phone',
                'teachers.employee_id',
                'teachers.department',
                'teachers.designation_id',
                'teachers.subject',
                'teachers.status',
                'teachers.school_id',
                'teachers.created_at',
            ],
            'default_sort' => 'name',
            'with' => ['designation:id,name,code'],
        ]);
    }

    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);

        return $this->success(new TeacherResource($teacher->load('designation')));
    }

    public function store(StoreTeacherRequest $request)
    {
        $firstName = $request->firstName ?? explode(' ', $request->name)[0] ?? '';
        $surname = $request->surname ?? (count(explode(' ', $request->name ?? '') ?? []) > 1
            ? implode(' ', array_slice(explode(' ', $request->name), 1))
            : '');

        $school = $request->user()->school;
        $defaultCurrency = $school?->getDefaultCurrency() ?? 'USD';

        $teacher = Teacher::create([
            'name' => trim($firstName.' '.$surname) ?: $request->name,
            'first_name' => $firstName,
            'last_name' => $surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'subject' => $request->subject,
            'department' => $request->department,
            'designation_id' => $request->input('designation_id'),
            'qualification' => $request->qualification,
            'joining_date' => $request->joiningDate,
            'employee_id' => $this->staffNumbers->generateEmployeeNumber((int) $request->user()->school_id),
            'status' => $request->input('status', 'active'),
            'employment_type' => $request->input('employment_type', 'full_time'),
            'base_salary' => $request->input('base_salary'),
            'period_rate' => $request->input('period_rate'),
            'salary_currency' => $request->input('salary_currency', $defaultCurrency),
            'allowances' => $request->input('allowances'),
            'deductions' => $request->input('deductions'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_number' => $request->input('bank_account_number'),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
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

        $teacher->fill($request->only([
            'email',
            'phone',
            'address',
            'subject',
            'department',
            'designation_id',
            'qualification',
            'status',
            'employment_type',
            'base_salary',
            'period_rate',
            'salary_currency',
            'allowances',
            'deductions',
            'bank_name',
            'bank_account_number',
            'payment_method',
        ]));
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
