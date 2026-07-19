<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\School\RegisterSchoolRequest;
use App\Http\Requests\Api\V1\School\UpdateSchoolRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Models\School;
use App\Services\SchoolProvisioningService;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function __construct(
        private SchoolProvisioningService $provisioning,
    ) {}

    public function register(RegisterSchoolRequest $request)
    {
        $result = $this->provisioning->provision($request->validated());

        return response()->json([
            'message' => 'School registered successfully. You can now log in.',
            'school_id' => $result['school']->id,
            'data' => [
                'school_id' => $result['school']->id,
                'school' => (new SchoolResource($result['school']))->resolve(),
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
