<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AbacPolicy;
use App\Models\ComplianceRequirement;
use App\Models\CrossSchoolTransfer;
use App\Models\GroupPolicy;
use App\Models\IntegrationConnector;
use App\Models\PerformanceReview;
use App\Models\StaffCertification;
use App\Models\StaffContract;
use App\Models\WebhookSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EnterpriseGovernanceController extends Controller
{
    private function authorizeEnterpriseGovernance(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['hr.manage', 'compliance.manage', 'settings.manage'],
        );
    }

    public function performanceReviews(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => PerformanceReview::where('school_id', $request->user()->school_id)->limit(100)->get()]);
    }

    public function storePerformanceReview(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'staff_user_id' => 'required|exists:users,id',
            'period' => 'required|string',
            'overall_score' => 'nullable|numeric',
            'summary' => 'nullable|string',
            'criteria_scores' => 'nullable|array',
        ])->validate();

        return response()->json(['data' => PerformanceReview::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function contracts(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => StaffContract::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeContract(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'contract_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'salary' => 'nullable|numeric',
        ])->validate();

        return response()->json(['data' => StaffContract::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function certifications(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => StaffCertification::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeCertification(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'certification_name' => 'required|string',
            'issuer' => 'nullable|string',
            'issued_on' => 'nullable|date',
            'expires_on' => 'nullable|date',
        ])->validate();

        return response()->json(['data' => StaffCertification::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function complianceRequirements(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => ComplianceRequirement::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeComplianceRequirement(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'authority' => 'required|string',
            'requirement_code' => 'required|string',
            'title' => 'required|string',
            'due_date' => 'nullable|date',
        ])->validate();

        return response()->json(['data' => ComplianceRequirement::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function connectors(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => IntegrationConnector::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeConnector(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'provider' => 'required|string',
            'connector_type' => 'required|in:payment,sms,lms,accounting,government',
            'config' => 'required|array',
            'is_active' => 'boolean',
        ])->validate();

        return response()->json(['data' => IntegrationConnector::updateOrCreate(
            ['school_id' => $request->user()->school_id, 'provider' => $data['provider'], 'connector_type' => $data['connector_type']],
            $data,
        )]);
    }

    public function abacPolicies(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => AbacPolicy::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeAbacPolicy(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'resource' => 'required|string',
            'conditions' => 'required|array',
            'effect' => 'in:allow,deny',
        ])->validate();

        return response()->json(['data' => AbacPolicy::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function groupPolicies(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => GroupPolicy::where('parent_school_id', $request->user()->school_id)->get()]);
    }

    public function storeGroupPolicy(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'policy_code' => 'required|string',
            'name' => 'required|string',
            'rules' => 'required|array',
            'enforce_on_branches' => 'boolean',
        ])->validate();

        return response()->json(['data' => GroupPolicy::create(array_merge($data, ['parent_school_id' => $request->user()->school_id]))], 201);
    }

    public function crossSchoolTransfers(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        return response()->json(['data' => CrossSchoolTransfer::where('from_school_id', $request->user()->school_id)->orWhere('to_school_id', $request->user()->school_id)->limit(100)->get()]);
    }

    public function requestTransfer(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'to_school_id' => 'required|exists:schools,id',
        ])->validate();

        return response()->json(['data' => CrossSchoolTransfer::create([
            'student_id' => $data['student_id'],
            'from_school_id' => $request->user()->school_id,
            'to_school_id' => $data['to_school_id'],
            'requested_by' => $request->user()->id,
        ])], 201);
    }

    public function storeWebhook(Request $request)
    {
        $this->authorizeEnterpriseGovernance($request);

        $data = Validator::make($request->all(), [
            'event_type' => 'required|string',
            'target_url' => 'required|url',
        ])->validate();

        $secret = Str::random(32);

        $webhook = WebhookSubscription::create([
            'school_id' => $request->user()->school_id,
            'event_type' => $data['event_type'],
            'target_url' => $data['target_url'],
            'secret_hash' => Hash::make($secret),
        ]);

        return response()->json(['data' => $webhook, 'secret' => $secret], 201);
    }
}
