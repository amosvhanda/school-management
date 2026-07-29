<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ResolvesPlatformSchoolScope;
use App\Http\Resources\Api\V1\RefundResource;
use App\Models\FeePenaltyRule;
use App\Models\Payment;
use App\Models\PaymentGatewayConfig;
use App\Models\Refund;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\Student;
use App\Services\Platform\PaymentGatewayService;
use App\Services\Platform\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlatformFinanceController extends Controller
{
    use ResolvesPlatformSchoolScope;

    public function __construct(
        private PaymentGatewayService $gateway,
        private RefundService $refunds,
    ) {}

    private function authorizePlatformFinance(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );
    }


    public function scholarships(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $rows = $this->scopeToPlatformSchool(Scholarship::query(), $request)
            ->with('school:id,name,code')
            ->withCount('applications')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function storeScholarship(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'name' => 'required|string',
            'type' => 'required|string',
            'amount' => 'nullable|numeric',
            'percentage' => 'nullable|numeric',
            'criteria' => 'nullable|string',
            'application_deadline' => 'nullable|date',
            'slots' => 'nullable|integer',
            'status' => 'nullable|string',
        ])->validate();

        $scholarship = Scholarship::create(array_merge($data, ['school_id' => $schoolId]));

        return response()->json(['data' => $scholarship, 'message' => 'Scholarship created'], 201);
    }

    public function applyScholarship(Request $request, int $id)
    {
        $this->authorizePlatformFinance($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $scholarship = Scholarship::where('school_id', $schoolId)->findOrFail($id);
        $data = Validator::make($request->all(), [
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'motivation' => 'nullable|string',
            'supporting_data' => 'array',
        ])->validate();

        $application = ScholarshipApplication::create([
            'scholarship_id' => $scholarship->id,
            'student_id' => $data['student_id'],
            'school_id' => $schoolId,
            'motivation' => $data['motivation'] ?? null,
            'supporting_data' => $data['supporting_data'] ?? null,
        ]);

        return response()->json(['data' => $application, 'message' => 'Application submitted'], 201);
    }

    public function reviewScholarshipApplication(Request $request, int $id)
    {
        $this->authorizePlatformFinance($request);

        $application = $this->scopeToPlatformSchool(ScholarshipApplication::query(), $request)->findOrFail($id);
        $data = Validator::make($request->all(), ['status' => 'required|in:approved,rejected,pending'])->validate();

        $application->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['data' => $application->fresh(), 'message' => 'Application reviewed']);
    }

    public function gatewayConfigs(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $rows = $this->scopeToPlatformSchool(PaymentGatewayConfig::query(), $request)
            ->with('school:id,name,code')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function storeGatewayConfig(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'provider' => 'required|string',
            'credentials' => 'required|array',
            'is_active' => 'boolean',
            'supports_cards' => 'boolean',
            'supports_mobile_money' => 'boolean',
            'supports_bank_transfer' => 'boolean',
        ])->validate();

        $config = PaymentGatewayConfig::updateOrCreate(
            ['school_id' => $schoolId, 'provider' => $data['provider']],
            $data,
        );

        return response()->json([
            'data' => $config->fresh(),
            'message' => 'Gateway config saved',
        ]);
    }

    public function initiatePayment(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $invoiceRule = Rule::exists('invoices', 'id')->where('school_id', $schoolId);
        $studentRule = Rule::exists('students', 'id')->where('school_id', $schoolId);

        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'invoice_id' => ['required', $invoiceRule],
            'student_id' => ['nullable', $studentRule],
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:card,mobile_money,bank_transfer',
            'provider' => 'nullable|string',
        ])->validate();

        $txn = $this->gateway->initiate(
            $schoolId,
            $data['invoice_id'],
            $data['student_id'] ?? null,
            $data['amount'],
            $data['payment_method'],
            $data['provider'] ?? 'stripe',
        );

        return response()->json(['data' => $txn, 'message' => 'Payment initiated'], 201);
    }

    public function penaltyRules(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $rows = $this->scopeToPlatformSchool(FeePenaltyRule::query(), $request)
            ->with('school:id,name,code')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function storePenaltyRule(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'name' => 'required|string',
            'grace_days' => 'integer|min:0',
            'penalty_type' => 'required|in:fixed,percentage',
            'penalty_value' => 'required|numeric|min:0',
            'frequency' => 'in:once,daily,monthly',
            'is_active' => 'boolean',
        ])->validate();

        $rule = FeePenaltyRule::create(array_merge($data, ['school_id' => $schoolId]));

        return response()->json(['data' => $rule, 'message' => 'Penalty rule created'], 201);
    }

    public function refunds(Request $request)
    {
        $this->authorizePlatformFinance($request);

        return RefundResource::collection(
            $this->scopeToPlatformSchool(Refund::query(), $request)
                ->with(['payment', 'school:id,name,code'])
                ->orderByDesc('id')
                ->limit(200)
                ->get()
        )->additional(['message' => 'Success']);
    }

    public function requestRefund(Request $request)
    {
        $this->authorizePlatformFinance($request);

        $schoolId = $this->requirePlatformSchoolId($request);
        $paymentRule = Rule::exists('payments', 'id')->where('school_id', $schoolId);
        $data = Validator::make($request->all(), [
            'school_id' => 'nullable|integer|exists:schools,id',
            'payment_id' => ['required', $paymentRule],
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
        ])->validate();

        $payment = Payment::where('school_id', $schoolId)->findOrFail($data['payment_id']);
        $refund = $this->refunds->request($request->user(), $payment, $data['amount'], $data['reason']);

        return (new RefundResource($refund->load('payment')))
            ->additional(['message' => 'Refund requested'])
            ->response()
            ->setStatusCode(201);
    }

    public function approveRefund(Request $request, Refund $refund)
    {
        $this->authorizePlatformFinance($request);

        return (new RefundResource(
            $this->refunds->approve($refund, $request->user())->load('payment')
        ))->additional(['message' => 'Refund processed']);
    }
}
