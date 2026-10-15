<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\BankStatementLine;
use App\Models\ChartOfAccount;
use App\Models\ExchangeRate;
use App\Models\InstalmentPlan;
use App\Models\Payment;
use App\Models\RevenueRecognitionRule;
use App\Services\Enterprise\FinanceEnterpriseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

    public function postJournal(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $data = Validator::make($request->all(), [
            'description' => 'required|string',
            'entry_date' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('school_id', $request->user()->school_id),
            ],
            'lines.*.debit' => 'nullable|numeric',
            'lines.*.credit' => 'nullable|numeric',
        ])->validate();

        $entry = $this->finance->postJournal($request->user()->school_id, $data, $request->user()->id);

        return response()->json(['data' => $entry, 'message' => 'Journal posted'], 201);
    }

    public function exchangeRates(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => ExchangeRate::where('school_id', $request->user()->school_id)->orderByDesc('effective_date')->get()]);
    }

    public function storeExchangeRate(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $data = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
        ])->validate();

        return response()->json(['data' => ExchangeRate::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function instalmentPlans(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => InstalmentPlan::where('school_id', $request->user()->school_id)->with('items')->get()]);
    }

    public function createInstalmentPlan(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $data = Validator::make($request->all(), [
            'student_id' => [
                'required',
                Rule::exists('students', 'id')
                    ->where('school_id', $request->user()->school_id),
            ],
            'invoice_id' => [
                'nullable',
                Rule::exists('invoices', 'id')
                    ->where('school_id', $request->user()->school_id),
            ],
            'total_amount' => 'required|numeric',
            'currency' => 'nullable|string|size:3',
            'schedule' => 'required|array|min:1',
            'schedule.*.due_date' => 'required|date',
            'schedule.*.amount' => 'required|numeric',
        ])->validate();

        return response()->json([
            'data' => $this->finance->createInstalmentPlan($request->user()->school_id, $data),
        ], 201);
    }

    public function bankStatements(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        return response()->json(['data' => BankStatementLine::where('school_id', $request->user()->school_id)->orderByDesc('transaction_date')->limit(100)->get()]);
    }

    public function importBankLine(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $data = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'reference' => 'nullable|string',
            'description' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
        ])->validate();

        return response()->json(['data' => BankStatementLine::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }

    public function reconcile(Request $request, BankStatementLine $line)
    {
        $this->authorizeEnterpriseFinance($request);

        $data = Validator::make($request->all(), [
            'payment_id' => [
                'required',
                Rule::exists('payments', 'id')
                    ->where('school_id', $request->user()->school_id),
            ],
        ])->validate();

        $payment = Payment::where('school_id', $request->user()->school_id)->findOrFail($data['payment_id']);

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

    public function storeRevenueRule(Request $request)
    {
        $this->authorizeEnterpriseFinance($request);

        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'recognition_method' => 'required|in:immediate,monthly,term_based',
            'config' => 'nullable|array',
        ])->validate();

        return response()->json(['data' => RevenueRecognitionRule::create(array_merge($data, ['school_id' => $request->user()->school_id]))], 201);
    }
}
