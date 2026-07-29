<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Settings\StoreCustomFieldRequest;
use App\Http\Requests\Api\V1\Settings\UpdateCustomFieldRequest;
use App\Http\Requests\Api\V1\Settings\UpdateSchoolSettingsRequest;
use App\Http\Requests\Api\V1\Settings\UpdateTerminologyRequest;
use App\Http\Resources\Api\V1\CustomFieldResource;
use App\Models\CustomField;
use App\Models\School;
use App\Services\CustomFieldService;
use App\Services\SchoolSettingsService;
use App\Services\TerminologyService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SchoolSettingsService $settingsService,
        private TerminologyService $terminologyService,
        private CustomFieldService $customFieldService,
    ) {}

    private function authorizeSettingsManage(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['settings.manage'],
        );
    }


    public function schoolSettings(Request $request)
    {
        $school = School::findOrFail($request->user()->school_id);

        return $this->success($this->settingsService->getAll($school));
    }

    public function updateSchoolSettings(UpdateSchoolSettingsRequest $request)
    {
        $this->authorizeSettingsManage(request());
        $school = School::findOrFail($request->user()->school_id);
        $this->settingsService->bulkSet($school, $request->validated('settings'));

        return $this->success(
            $this->settingsService->getAll($school),
            'School settings updated successfully'
        );
    }

    public function publicConfig(Request $request)
    {
        $schoolId = $request->user()->school_id;

        if ($schoolId === null) {
            return $this->success([
                'settings' => [],
                'terminology' => [],
                'custom_fields' => [],
            ]);
        }

        $school = School::findOrFail($schoolId);
        $locale = $request->get('locale', 'en');

        return $this->success([
            'settings' => $this->settingsService->getAll($school, publicOnly: true),
            'terminology' => $this->terminologyService->getForSchool($school, $locale),
        ]);
    }

    public function terminology(Request $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $locale = $request->get('locale', 'en');

        return $this->success([
            'locale' => $locale,
            'mappings' => $this->terminologyService->getForSchool($school, $locale),
        ]);
    }

    public function updateTerminology(UpdateTerminologyRequest $request)
    {
        $this->authorizeSettingsManage(request());
        $school = School::findOrFail($request->user()->school_id);
        $locale = $request->get('locale', 'en');

        $this->terminologyService->bulkSet($school, $request->validated('mappings'), $locale);

        return $this->success([
            'locale' => $locale,
            'mappings' => $this->terminologyService->getForSchool($school, $locale),
        ], 'Terminology updated successfully');
    }

    public function customFields(Request $request)
    {
        $this->authorize('viewAny', CustomField::class);

        $school = School::findOrFail($request->user()->school_id);
        $entityType = $request->get('entity_type');

        // No entity_type → return all school fields (admin settings list).
        // With entity_type → scoped list for student/teacher forms.
        $fields = $this->customFieldService->listForEntity(
            $school,
            is_string($entityType) && $entityType !== '' ? $entityType : null,
        );

        return $this->success(CustomFieldResource::collection($fields));
    }

    public function storeCustomField(StoreCustomFieldRequest $request)
    {
        $this->authorizeSettingsManage(request());
        $school = School::findOrFail($request->user()->school_id);
        $field = $this->customFieldService->create($school, $request->validated());

        return $this->created(new CustomFieldResource($field), 'Custom field created successfully');
    }

    public function updateCustomField(UpdateCustomFieldRequest $request, CustomField $customField)
    {
        $this->authorizeSettingsManage(request());
        $field = $this->customFieldService->update($customField, $request->validated());

        return $this->success(new CustomFieldResource($field), 'Custom field updated successfully');
    }

    public function destroyCustomField(CustomField $customField)
    {
        $this->authorizeSettingsManage(request());
        $this->authorize('delete', $customField);
        $customField->delete();

        return $this->success(message: 'Custom field deleted successfully');
    }
}
