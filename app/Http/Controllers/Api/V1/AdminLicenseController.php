<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LicenseKey;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminLicenseController extends Controller
{
    public function __construct(private LicenseService $licenses) {}

    public function index(Request $request)
    {
        $filters = Validator::make($request->all(), [
            'status' => 'nullable|string|in:unused,active,expired,revoked',
            'school_id' => 'nullable|integer|exists:schools,id',
        ])->validate();

        return $this->success([
            'summary' => $this->licenses->platformSummary(),
            'keys' => $this->licenses->listKeys($filters)->map(fn (LicenseKey $key) => [
                'id' => $key->id,
                'key_prefix' => $key->key_prefix,
                'plan_type' => $key->plan_type->value,
                'duration_months' => $key->duration_months,
                'amount' => $key->amount !== null ? (float) $key->amount : null,
                'currency' => $key->currency,
                'status' => $key->status->value,
                'school' => $key->school ? [
                    'id' => $key->school->id,
                    'name' => $key->school->name,
                    'code' => $key->school->code,
                ] : null,
                'customer_name' => $key->customer_name,
                'customer_email' => $key->customer_email,
                'activated_at' => $key->activated_at?->toIso8601String(),
                'expires_at' => $key->expires_at?->toIso8601String(),
                'created_at' => $key->created_at?->toIso8601String(),
                'created_by' => $key->creator ? [
                    'id' => $key->creator->id,
                    'name' => $key->creator->name,
                    'email' => $key->creator->email,
                ] : null,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'plan_type' => 'required|string|in:lifetime,monthly,quarterly,annual,custom',
            'duration_months' => 'nullable|integer|min:1|max:120',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ])->validate();

        $result = $this->licenses->generate($request->user(), $data);

        return $this->success([
            'license_key' => $result['license_key'],
            'record' => [
                'id' => $result['record']->id,
                'key_prefix' => $result['record']->key_prefix,
                'plan_type' => $result['record']->plan_type->value,
                'duration_months' => $result['record']->duration_months,
                'status' => $result['record']->status->value,
                'customer_name' => $result['record']->customer_name,
                'customer_email' => $result['record']->customer_email,
            ],
        ], 'License key generated. Store it securely — it cannot be retrieved again.', 201);
    }

    public function revoke(Request $request, int $id)
    {
        $key = LicenseKey::findOrFail($id);
        $key = $this->licenses->revoke($key);

        return $this->success([
            'id' => $key->id,
            'status' => $key->status->value,
        ], 'License key revoked.');
    }
}
