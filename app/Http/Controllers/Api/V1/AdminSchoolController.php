<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\School\ProvisionSchoolRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Services\LicenseService;
use App\Services\SchoolProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSchoolController extends Controller
{
    public function __construct(
        private LicenseService $licenses,
        private SchoolProvisioningService $provisioning,
    ) {}

    public function index(Request $request)
    {
        $filters = Validator::make($request->all(), [
            'license_status' => 'nullable|string|in:licensed,unlicensed,active,grace,expired,none',
            'search' => 'nullable|string|max:100',
        ])->validate();

        return $this->success([
            'schools' => $this->licenses->listSchools($filters),
            'summary' => $this->licenses->platformSummary(),
        ]);
    }

    /**
     * Super admin registers a school with profile + school admin account.
     */
    public function store(ProvisionSchoolRequest $request)
    {
        $result = $this->provisioning->provision($request->validated(), $request->user());

        return $this->success([
            'school' => (new SchoolResource($result['school']))->resolve(),
            'admin' => [
                'id' => $result['admin']->id,
                'name' => $result['admin']->name,
                'email' => $result['admin']->email,
                'role' => $result['admin']->roleValue(),
            ],
            'license_key' => $result['license_key'],
            'license_status' => $result['license_status'],
            'login_hint' => 'The school admin can sign in with the email and password you set.',
        ], 'School registered. The school admin can now sign in and manage the school.', 201);
    }
}
