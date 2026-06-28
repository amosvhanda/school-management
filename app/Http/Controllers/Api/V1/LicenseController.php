<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $licenses) {}

    public function status(Request $request)
    {
        $school = $request->user()->school;

        if (! $school) {
            return $this->error('Your account is not linked to a school.', 403);
        }

        return $this->success($this->licenses->status($school));
    }

    public function activate(Request $request)
    {
        $school = $request->user()->school;

        if (! $school) {
            return $this->error('Your account is not linked to a school.', 403);
        }

        $data = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'min:10'],
        ])->validate();

        $key = $this->licenses->activate($school, $data['license_key']);

        return $this->success([
            'license' => $this->licenses->status($school->fresh()),
            'activated_key' => [
                'id' => $key->id,
                'plan_type' => $key->plan_type->value,
                'expires_at' => $key->expires_at?->toIso8601String(),
            ],
        ], 'License activated successfully.');
    }
}
