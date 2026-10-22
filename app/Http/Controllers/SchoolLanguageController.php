<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesSchoolResourceCrud;
use App\Models\SchoolLanguage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolLanguageController extends Controller
{
    use ManagesSchoolResourceCrud;

    protected function resourceModel(): string
    {
        return SchoolLanguage::class;
    }

    protected function manageCapabilities(): array
    {
        return ['canManageFinance'];
    }

    protected function managePermissionSlugs(): array
    {
        return ['settings.manage', 'finance.manage'];
    }

    protected function resourceLabel(): string
    {
        return 'School language';
    }

    protected function storeRules(Request $request, int $schoolId): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('school_languages')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'name' => ['required', 'string', 'max:255'],
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
                'max:10',
                Rule::unique('school_languages')->where(fn ($q) => $q->where('school_id', $schoolId))->ignore($id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function afterStore(Request $request, Model $record): void
    {
        $this->ensureSingleDefault($record);
    }

    protected function afterUpdate(Request $request, Model $record): void
    {
        $this->ensureSingleDefault($record);
    }

    protected function ensureSingleDefault(Model $record): void
    {
        /** @var SchoolLanguage $record */
        if (! $record->is_default) {
            return;
        }

        SchoolLanguage::query()
            ->where('school_id', $record->school_id)
            ->whereKeyNot($record->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
