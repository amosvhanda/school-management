<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return Employee::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageTeachers'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['hr.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'Employee';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'employee_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'designation_id' => [
                'nullable',
                'integer',
                Rule::exists('designations', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'employee_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees')->where(fn ($q) => $q->where('school_id', $schoolId))->ignore($id),
            ],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'designation_id' => [
                'nullable',
                'integer',
                Rule::exists('designations', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeManage($request);

        $query = $this->applyIndexFilters($this->schoolQuery($request), $request)
            ->with(['designation:id,name', 'department:id,name']);
        $this->orderIndex($query);

        return response()->json(['data' => $query->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        $record = $this->schoolQuery($request)
            ->with(['designation:id,name', 'department:id,name'])
            ->findOrFail($id);

        return response()->json(['data' => $record]);
    }

    protected function applyIndexFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query;
    }

    protected function afterStore(Request $request, Model $record): void
    {
        $this->syncEmployeeName($record);
    }

    protected function afterUpdate(Request $request, Model $record): void
    {
        $this->syncEmployeeName($record);
    }

    protected function syncEmployeeName(Model $record): void
    {
        /** @var Employee $record */
        $name = trim($record->first_name.' '.$record->last_name);
        if ($record->name !== $name) {
            $record->name = $name;
            $record->saveQuietly();
        }
    }
}
