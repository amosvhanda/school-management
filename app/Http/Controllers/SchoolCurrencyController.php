<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\School;
use App\Models\SchoolCurrency;
use App\Services\SchoolConfigurationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolCurrencyController extends Controller
{
    use ManagesSchoolResourceCrud;

    public function __construct(private SchoolConfigurationService $schoolConfig) {}

    protected function resourceModel(): string
    {
        return SchoolCurrency::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageTeachers', 'canManageFinance'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['settings.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'School currency';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'code' => [
                'required',
                'string',
                'size:3',
                Rule::unique('school_currencies')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:8'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(Request $request, int $schoolId, int $id): array
    {
        return [
            'code' => [
                'sometimes',
                'string',
                'size:3',
                Rule::unique('school_currencies')->where(fn ($q) => $q->where('school_id', $schoolId))->ignore($id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:8'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function afterStore(Request $request, Model $record): void
    {
        $this->ensureSingleDefault($record);
        $this->syncSchoolBillingCurrency($record);
    }

    protected function afterUpdate(Request $request, Model $record): void
    {
        $this->ensureSingleDefault($record);
        $this->syncSchoolBillingCurrency($record);
    }

    protected function ensureSingleDefault(Model $record): void
    {
        /** @var SchoolCurrency $record */
        if (! $record->is_default) {
            return;
        }

        SchoolCurrency::query()
            ->where('school_id', $record->school_id)
            ->whereKeyNot($record->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    protected function syncSchoolBillingCurrency(Model $record): void
    {
        /** @var SchoolCurrency $record */
        if (! $record->is_default) {
            return;
        }

        $school = School::query()->find($record->school_id);
        if (! $school) {
            return;
        }

        $code = strtoupper((string) $record->code);

        try {
            if ($school->validateCurrency($code)) {
                $this->schoolConfig->setSchoolCurrency($school, $code);
            } else {
                $school->update(['currency_default' => $code]);
            }
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'is_default' => $e->errors()['currency'] ?? ['Unable to set this currency as the school default.'],
            ]);
        }
    }
}
