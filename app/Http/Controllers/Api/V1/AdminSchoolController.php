<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\School\ProvisionSchoolRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Models\School;
use App\Models\SchoolBackup;
use App\Models\SchoolDomain;
use App\Services\LicenseService;
use App\Services\SchoolProvisioningService;
use App\Services\Tenancy\SchoolBackupService;
use App\Services\Tenancy\SchoolDomainVerificationService;
use App\Services\Tenancy\SchoolUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminSchoolController extends Controller
{
    public function __construct(
        private LicenseService $licenses,
        private SchoolProvisioningService $provisioning,
        private SchoolBackupService $backups,
        private SchoolDomainVerificationService $domainVerification,
        private SchoolUsageService $usage,
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

    public function show(School $school)
    {
        return $this->success([
            'school' => (new SchoolResource($school))->resolve(),
            'domains' => $school->domains()->orderByDesc('is_primary')->orderBy('domain')->get()->map(
                fn (SchoolDomain $domain) => $this->mapDomain($domain)
            )->values(),
            'usage' => $this->usage->snapshot($school),
        ]);
    }

    public function usage(School $school)
    {
        return $this->success([
            'usage' => $this->usage->snapshot($school),
        ]);
    }

    public function updateStatus(Request $request, School $school)
    {
        $data = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ])->validate();

        $school->update(['status' => $data['status']]);

        return $this->success([
            'school' => (new SchoolResource($school->fresh()))->resolve(),
        ], $data['status'] === 'active' ? 'School activated.' : 'School suspended.');
    }

    public function destroy(Request $request, School $school)
    {
        $data = Validator::make($request->all(), [
            'backup' => ['sometimes', 'boolean'],
        ])->validate();

        $backup = null;
        if (($data['backup'] ?? true) === true) {
            $backup = $this->backups->create($school, $request->user());
        }

        $school->update([
            'status' => 'deleted',
            'license_status' => 'expired',
        ]);
        $school->domains()->update(['status' => 'inactive']);

        return $this->success([
            'school' => (new SchoolResource($school->fresh()))->resolve(),
            'backup' => $backup ? $this->backups->mapBackup($backup) : null,
        ], 'School deprovisioned.');
    }

    public function listBackups(School $school)
    {
        return $this->success([
            'backups' => $this->backups->listForSchool($school),
        ]);
    }

    public function createBackup(Request $request, School $school)
    {
        $backup = $this->backups->create($school, $request->user());

        return $this->success([
            'backup' => $this->backups->mapBackup($backup),
        ], 'School backup created.', 201);
    }

    public function restoreBackup(School $school, SchoolBackup $backup)
    {
        abort_unless($backup->school_id === $school->id, 404);

        $restored = $this->backups->restore($backup);

        return $this->success([
            'backup' => $this->backups->mapBackup($restored),
        ], 'School backup restored.');
    }

    public function domains(School $school)
    {
        return $this->success([
            'domains' => $school->domains()->orderByDesc('is_primary')->orderBy('domain')->get()->map(
                fn (SchoolDomain $domain) => $this->mapDomain($domain)
            )->values(),
        ]);
    }

    public function storeDomain(Request $request, School $school)
    {
        $data = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255', 'unique:school_domains,domain'],
            'is_primary' => ['sometimes', 'boolean'],
        ])->validate();

        if (($data['is_primary'] ?? false) === true) {
            $school->domains()->update(['is_primary' => false]);
        }

        $domain = $school->domains()->create([
            'domain' => strtolower(trim($data['domain'])),
            'verification_token' => $this->domainVerification->generateToken(),
            'is_primary' => (bool) ($data['is_primary'] ?? false),
            'is_verified' => false,
            'status' => 'active',
        ]);

        return $this->success([
            'domain' => $this->mapDomain($domain),
        ], 'School domain added.', 201);
    }

    public function updateDomain(Request $request, School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);

        $data = Validator::make($request->all(), [
            'domain' => ['sometimes', 'string', 'max:255', Rule::unique('school_domains', 'domain')->ignore($domain->id)],
            'is_primary' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ])->validate();

        if (($data['is_primary'] ?? false) === true) {
            $school->domains()->where('id', '!=', $domain->id)->update(['is_primary' => false]);
        }

        if (isset($data['domain'])) {
            $data['domain'] = strtolower(trim($data['domain']));
            if ($data['domain'] !== $domain->domain) {
                $data['is_verified'] = false;
                $data['verified_at'] = null;
                $data['verification_token'] = $this->domainVerification->generateToken();
            }
        }

        $domain->update($data);

        return $this->success([
            'domain' => $this->mapDomain($domain->fresh()),
        ], 'School domain updated.');
    }

    public function checkDomainVerification(School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);

        return $this->success([
            'domain' => $this->mapDomain($domain),
            'dns_verified' => $this->domainVerification->verifyDns($domain),
        ]);
    }

    public function verifyDomain(Request $request, School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);

        $force = $request->boolean('force');
        if (! $force && ! $this->domainVerification->verifyDns($domain)) {
            return response()->json([
                'message' => 'DNS verification failed. Add the TXT record shown in verification instructions, or pass force=1 to verify manually.',
                'code' => 'domain_verification_failed',
                'data' => [
                    'domain' => $this->mapDomain($domain),
                    'verification' => $this->domainVerification->instructions($domain),
                ],
            ], 422);
        }

        $domain->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => 'active',
        ]);

        return $this->success([
            'domain' => $this->mapDomain($domain->fresh()),
        ], 'School domain verified.');
    }

    public function destroyDomain(School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);
        $domain->delete();

        return $this->success(message: 'School domain deleted.');
    }

    private function assertDomainBelongsToSchool(School $school, SchoolDomain $domain): void
    {
        abort_unless($domain->school_id === $school->id, 404);
    }

    private function mapDomain(SchoolDomain $domain): array
    {
        if (! $domain->verification_token) {
            $domain->update([
                'verification_token' => $this->domainVerification->generateToken(),
            ]);
            $domain->refresh();
        }

        return [
            'id' => $domain->id,
            'school_id' => $domain->school_id,
            'domain' => $domain->domain,
            'is_primary' => (bool) $domain->is_primary,
            'is_verified' => (bool) $domain->is_verified,
            'status' => $domain->status,
            'verified_at' => $domain->verified_at?->toIso8601String(),
            'verification' => $this->domainVerification->instructions($domain),
            'created_at' => $domain->created_at?->toIso8601String(),
            'updated_at' => $domain->updated_at?->toIso8601String(),
        ];
    }
}
