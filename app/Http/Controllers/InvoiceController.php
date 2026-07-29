<?php

namespace App\Http\Controllers;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Invoice;
use App\Models\NotificationQueue;
use App\Models\Student;
use App\Services\FinancialLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\AllowedFilter;

class InvoiceController extends Controller
{
    use HandlesResourceQueries;

    public function __construct(private FinancialLedgerService $ledgerService) {}

    public function index(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        $schoolId = $request->user()->school_id;

        if ($schoolId) {
            $this->ledgerService->syncOverdueInvoices($schoolId);
        }

        $base = Invoice::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        return $this->paginateResource($request, Invoice::class, InvoiceResource::class, [
            'base' => $base,
            'filters' => [
                'student_id',
                AllowedFilter::callback('status', function ($query, $value) {
                    $statuses = is_array($value) ? $value : explode(',', (string) $value);
                    $query->whereIn('status', array_filter(array_map('trim', $statuses)));
                }),
                'currency',
                AllowedFilter::callback('from', fn ($query, $value) => $query->whereDate('due_date', '>=', $value)),
                AllowedFilter::callback('to', fn ($query, $value) => $query->whereDate('due_date', '<=', $value)),
                'search',
            ],
            'search_columns' => [
                'invoice_number',
                'description',
                'student.full_name',
                'student.student_number',
            ],
            'sorts' => ['created_at', 'due_date', 'amount', 'balance', 'status'],
            'includes' => ['student', 'payments', 'feeStructure'],
            'default_sort' => '-created_at',
            'with' => ['student:id,full_name,student_number'],
        ]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage', 'transactions.view'],
        );

        if ($request->user()->school_id && $invoice->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $invoice->load([
            'student:id,full_name,student_number,class,balance,currency',
            'payments' => fn ($q) => $q->orderByDesc('date')->limit(50),
            'feeStructure:id,category,amount',
        ]);

        return (new InvoiceResource($invoice))->additional(['message' => 'Success']);
    }

    public function store(Request $request)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        $schoolId = $request->user()->school_id;

        $validator = Validator::make($request->all(), [
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('school_id', $schoolId),
            ],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'fee_structure_id' => [
                'nullable',
                Rule::exists('fee_structures', 'id')->where('school_id', $schoolId),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $student = Student::where('school_id', $schoolId)->findOrFail($request->student_id);

        $invoice = $this->ledgerService->createInvoice(
            student: $student,
            amount: (float) $request->amount,
            description: $request->description,
            feeStructureId: $request->fee_structure_id ? (int) $request->fee_structure_id : null,
            dueDate: new \DateTimeImmutable($request->due_date),
            createdBy: $request->user()->id,
        );

        $this->sendInvoiceNotification($invoice, $student);

        return (new InvoiceResource($invoice->load(['student', 'school'])))
            ->additional(['message' => 'Invoice created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    protected function sendInvoiceNotification(Invoice $invoice, Student $student): void
    {
        $guardians = $student->guardians()->where('can_receive_notifications', true)->get();

        foreach ($guardians as $guardian) {
            if (! empty($guardian->email)) {
                NotificationQueue::create([
                    'school_id' => $invoice->school_id,
                    'type' => 'invoice_created',
                    'notifiable_type' => Student::class,
                    'notifiable_id' => $student->id,
                    'guardian_id' => $guardian->id,
                    'channel' => 'email',
                    'recipient_email' => $guardian->email,
                    'subject' => "New Invoice: {$invoice->invoice_number}",
                    'message' => "Dear {$guardian->full_name},\n\nA new invoice has been generated for {$student->full_name}.\n\nInvoice Number: {$invoice->invoice_number}\nAmount: {$invoice->currency} {$invoice->amount}\nDue Date: {$invoice->due_date->format('F j, Y')}\nDescription: {$invoice->description}\n\nPlease make payment before the due date.\n\nThank you.",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'currency' => $invoice->currency,
                    ],
                    'status' => 'pending',
                ]);
            }
        }

        if (! empty($student->email)) {
            NotificationQueue::create([
                'school_id' => $invoice->school_id,
                'type' => 'invoice_created',
                'notifiable_type' => Student::class,
                'notifiable_id' => $student->id,
                'channel' => 'email',
                'recipient_email' => $student->email,
                'subject' => "New Invoice: {$invoice->invoice_number}",
                'message' => "Dear {$student->full_name},\n\nA new invoice has been generated for you.\n\nInvoice Number: {$invoice->invoice_number}\nAmount: {$invoice->currency} {$invoice->amount}\nDue Date: {$invoice->due_date->format('F j, Y')}\nDescription: {$invoice->description}\n\nPlease ensure payment is made before the due date.\n\nThank you.",
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
                'status' => 'pending',
            ]);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageFinance'],
            permissionSlugs: ['finance.manage'],
        );

        if ($request->user()->school_id && $invoice->school_id !== $request->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($invoice->status, ['paid'], true)) {
            return response()->json(['message' => 'Paid invoices cannot be edited.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|string|max:500',
            'amount' => 'sometimes|numeric|min:0.01',
            'due_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('amount') && (float) $request->amount < (float) $invoice->amount_paid) {
            return response()->json([
                'message' => 'Invoice amount cannot be less than amount already paid.',
            ], 422);
        }

        if ($request->filled('description')) {
            $invoice->description = $request->input('description');
        }
        if ($request->filled('due_date')) {
            $invoice->due_date = $request->input('due_date');
        }

        if ($request->has('amount') && (float) $request->amount !== (float) $invoice->amount) {
            try {
                $invoice = $this->ledgerService->adjustInvoiceAmount(
                    $invoice,
                    (float) $request->amount,
                    $request->user()?->id,
                );
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        } else {
            $invoice->balance = max(0, round((float) $invoice->amount - (float) $invoice->amount_paid, 2));
            $invoice->status = $this->ledgerService->resolveInvoiceStatus($invoice);
            $invoice->save();
        }

        return (new InvoiceResource($invoice->fresh()))
            ->additional(['message' => 'Invoice updated successfully']);
    }
}
