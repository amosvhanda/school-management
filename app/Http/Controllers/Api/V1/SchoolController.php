<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\School\RegisterSchoolRequest;
use App\Http\Requests\Api\V1\School\UpdateSchoolRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Models\School;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\SchoolConfigurationService;
use App\Services\SchoolSettingsService;
use App\Services\TerminologyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SchoolController extends Controller
{
    public function __construct(
        private SchoolConfigurationService $configService,
        private SchoolSettingsService $settingsService,
        private TerminologyService $terminologyService,
        private LicenseService $licenseService,
    ) {}

    public function register(RegisterSchoolRequest $request)
    {
        $school = School::create([
            'name' => $request->school_name,
            'code' => $request->school_code,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'currency' => $request->currency ?? 'USD',
            'currency_default' => $request->currency ?? 'USD',
            'academic_year' => (string) now()->year,
            'current_term' => 'Term 1',
            'status' => 'active',
            'license_status' => config('license.enforcement') ? 'none' : 'active',
            'license_plan' => config('license.enforcement') ? null : 'lifetime',
            'license_expires_at' => null,
        ]);

        if (config('license.enforcement') && $request->filled('license_key')) {
            $this->licenseService->activate($school, $request->license_key);
            $school->refresh();
        }

        User::create([
            'name' => $request->admin_name,
            'first_name' => explode(' ', $request->admin_name)[0] ?? $request->admin_name,
            'last_name' => implode(' ', array_slice(explode(' ', $request->admin_name), 1)) ?: '',
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'role' => 'admin',
            'school_id' => $school->id,
        ]);

        $this->configService->initializeDefaultGradeLevels($school, $request->grade_levels);
        $this->configService->initializeDefaultGradingScale($school, $request->grading_scale);
        $this->settingsService->seedDefaults($school);
        $this->terminologyService->seedDefaults($school);

        return response()->json([
            'message' => 'School registered successfully. You can now log in.',
            'school_id' => $school->id,
            'data' => [
                'school_id' => $school->id,
                'school' => (new SchoolResource($school))->resolve(),
            ],
        ], 201);
    }

    public function show(Request $request)
    {
        $school = School::with(['gradeLevels', 'gradingScales', 'rooms'])
            ->findOrFail($request->user()->school_id);

        $this->authorize('view', $school);

        return $this->success(new SchoolResource($school));
    }

    public function update(UpdateSchoolRequest $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $this->authorize('update', $school);

        $school->update($request->validated());

        return $this->success(new SchoolResource($school->fresh()), 'School updated successfully');
    }
}
