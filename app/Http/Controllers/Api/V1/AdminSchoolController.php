<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSchoolController extends Controller
{
    public function __construct(private LicenseService $licenses) {}

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
}
