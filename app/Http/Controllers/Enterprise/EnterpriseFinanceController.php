<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Finance\CreateInstalmentPlanRequest;
use App\Http\Requests\Api\V1\Finance\ImportBankLineRequest;
use App\Http\Requests\Api\V1\Finance\PostJournalRequest;
use App\Http\Requests\Api\V1\Finance\ReconcileBankLineRequest;
use App\Http\Requests\Api\V1\Finance\StoreExchangeRateRequest;
use App\Http\Requests\Api\V1\Finance\StoreRevenueRuleRequest;
use App\Models\BankStatementLine;
use App\Models\ChartOfAccount;
use App\Models\ExchangeRate;
use App\Models\InstalmentPlan;
use App\Models\Payment;
use App\Models\RevenueRecognitionRule;
use App\Services\Enterprise\FinanceEnterpriseService;
use Illuminate\Http\Request;

class EnterpriseFinanceController extends Controller
{
    public function __construct(private FinanceEnterpriseService $finance) {}

    private function authorizeEnterpriseFinance(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );
    }

    public function seedAccounts(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $this->finance->seedDefaultAccounts($request->user()->school_id);

        return response()->json(['message' => 'Default chart of accounts created']);
    }

    public function accounts(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => ChartOfAccount::where('school_id', $request->user()->school_id)->orderBy('code')->get()]);
    }

    public function postJournal(PostJournalRequest $request)
    {
        $entry = $this->finance->postJournal(
            $request->user()->school_id,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json(['data' => $entry, 'message' => 'Journal posted'], 201);
    }

    public function exchangeRates(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => ExchangeRate::where('school_id', $request->user()->school_id)->orderByDesc('effective_date')->get()]);
    }

    public function storeExchangeRate(StoreExchangeRateRequest $request)
    {
        return response()->json([
            'data' => ExchangeRate::create(array_merge($request->validated(), [
                'school_id' => $request->user()->school_id,
            ])),
        ], 201);
    }

    public function instalmentPlans(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => InstalmentPlan::where('school_id', $request->user()->school_id)->with('items')->get()]);
    }

    public function createInstalmentPlan(CreateInstalmentPlanRequest $request)
    {
        return response()->json([
            'data' => $this->finance->createInstalmentPlan($request->user()->school_id, $request->validated()),
        ], 201);
    }

    public function bankStatements(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => BankStatementLine::where('school_id', $request->user()->school_id)->orderByDesc('transaction_date')->limit(100)->get()]);
    }

    public function importBankLine(ImportBankLineRequest $request)
    {
        return response()->json([
            'data' => BankStatementLine::create(array_merge($request->validated(), [
                'school_id' => $request->user()->school_id,
            ])),
        ], 201);
    }

    public function reconcile(ReconcileBankLineRequest $request, BankStatementLine $line)
    {
        $payment = Payment::where('school_id', $request->user()->school_id)
            ->findOrFail($request->validated('payment_id'));

        return response()->json(['data' => $this->finance->reconcileBankLine($line->id, $payment->id)]);
    }

    public function profitAndLoss(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $from = $request->get('from', now()->startOfYear()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return response()->json(['data' => $this->finance->profitAndLoss($request->user()->school_id, $from, $to)]);
    }

    public function balanceSheet(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => $this->finance->balanceSheet($request->user()->school_id, $request->get('as_of', now()->toDateString()))]);
    }

    public function cashflowForecast(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => $this->finance->cashflowForecast($request->user()->school_id, $request->integer('months') ?: 3)]);
    }

    public function revenueRules(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => RevenueRecognitionRule::where('school_id', $request->user()->school_id)->get()]);
    }

    public function storeRevenueRule(StoreRevenueRuleRequest $request)
    {
        return response()->json([
            'data' => RevenueRecognitionRule::create(array_merge($request->validated(), [
                'school_id' => $request->user()->school_id,
            ])),
        ], 201);
    }
}
