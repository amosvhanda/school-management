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
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\Enterprise\EnterpriseWebhookDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EnterpriseGovernanceController extends Controller
{
    public function performanceReviews(Request $request)
    {
        return response()->json(['data' => PerformanceReview::where('school_id', $request->user()->school_id)->limit(100)->get()]);
    }

    public function storePerformanceReview(Request $request)
    {
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
        return response()->json(['data' => StaffContract::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeContract(Request $request)
    {
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
        return response()->json(['data' => StaffCertification::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeCertification(Request $request)
    {
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
        return response()->json(['data' => ComplianceRequirement::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeComplianceRequirement(Request $request)
    {
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
        return response()->json(['data' => IntegrationConnector::where('school_id', $request->user()->school_id)->get()]);
    }

    public function webhooks(Request $request)
    {
        return response()->json([
            'data' => WebhookSubscription::query()
                ->where('school_id', $request->user()->school_id)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function webhookDeliveries(Request $request)
    {
        $query = WebhookDelivery::query()
            ->where('school_id', $request->user()->school_id)
            ->with('subscription:id,event_type,target_url')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json(['data' => $query->limit(200)->get()]);
    }

    public function retryWebhookDelivery(Request $request, int $id, EnterpriseWebhookDispatcher $dispatcher)
    {
        $delivery = WebhookDelivery::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($id);

        $dispatcher->retryDelivery($delivery);

        return response()->json([
            'data' => $delivery->fresh(),
            'message' => 'Webhook delivery queued for retry',
        ]);
    }

    public function updateWebhook(Request $request, int $id)
    {
        $webhook = WebhookSubscription::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($id);

        $data = Validator::make($request->all(), [
            'event_type' => 'sometimes|required|string|max:100',
            'target_url' => 'sometimes|required|url|max:500',
            'is_active' => 'boolean',
        ])->validate();

        $webhook->update($data);

        return response()->json(['data' => $webhook->fresh(), 'message' => 'Webhook updated']);
    }

    public function destroyWebhook(Request $request, int $id)
    {
        $webhook = WebhookSubscription::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($id);

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted']);
    }

    public function storeConnector(Request $request)
    {
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
        return response()->json(['data' => AbacPolicy::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeAbacPolicy(Request $request)
    {
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
        return response()->json(['data' => GroupPolicy::where('parent_school_id', $request->user()->school_id)->get()]);
    }

    public function storeGroupPolicy(Request $request)
    {
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
        return response()->json(['data' => CrossSchoolTransfer::where('from_school_id', $request->user()->school_id)->orWhere('to_school_id', $request->user()->school_id)->limit(100)->get()]);
    }

    public function requestTransfer(Request $request)
    {
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
        $data = Validator::make($request->all(), [
            'event_type' => 'required|string',
            'target_url' => 'required|url',
        ])->validate();

        $secret = Str::random(32);

        $webhook = WebhookSubscription::create([
            'school_id' => $request->user()->school_id,
            'event_type' => $data['event_type'],
            'target_url' => $data['target_url'],
            'secret' => $secret,
            'is_active' => true,
        ]);

        return response()->json(['data' => $webhook, 'secret' => $secret], 201);
    }
}
