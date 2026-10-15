<?php

namespace App\Http\Controllers;

use App\Models\ConsentForm;
use App\Models\ConsentResponse;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsentFormController extends Controller
{
    public function __construct(private ParentAccessService $parentAccess) {}

    private function authorizeConsentForms(Request $request): void
    {
        $this->authorizeModuleAccess(
            $request,
            capabilities: ['canManageTeachers'],
            permissionSlugs: ['compliance.manage'],
        );
    }


    public function index(Request $request)
    {
        $this->authorizeConsentForms($request);

        return response()->json([
            'data' => ConsentForm::where('school_id', $request->user()->school_id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeConsentForms($request);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'nullable|string',
            'due_date' => 'nullable|date',
            'requires_signature' => 'nullable|boolean',
        ]);

        $form = ConsentForm::create([
            ...$data,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $form], 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeConsentForms($request);

        $form = ConsentForm::where('school_id', $request->user()->school_id)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'target_audience' => 'nullable|string',
            'due_date' => 'nullable|date',
            'requires_signature' => 'nullable|boolean',
            'status' => 'nullable|string|in:active,archived',
        ]);

        $form->update($data);

        return response()->json(['data' => $form->fresh(), 'message' => 'Consent form updated']);
    }

    public function parentForms(Request $request)
    {
        $parent = $request->user();
        $studentIds = $this->parentAccess->accessibleStudentIds($parent)->map(fn ($id) => (int) $id)->values()->all();

        $forms = ConsentForm::where('school_id', $parent->school_id)
            ->where('status', 'active')
            ->whereIn('target_audience', ['parents', 'all'])
            ->orderByDesc('created_at')
            ->get();

        $responses = ConsentResponse::query()
            ->where('parent_user_id', $parent->id)
            ->whereIn('form_id', $forms->pluck('id'))
            ->get()
            ->groupBy('form_id');

        $data = $forms->map(function (ConsentForm $form) use ($responses, $studentIds) {
            $formResponses = $responses->get($form->id, collect());
            $byStudent = [];
            foreach ($formResponses as $response) {
                if ($response->student_id === null) {
                    continue;
                }
                $byStudent[(int) $response->student_id] = $response->status;
            }

            $pendingStudentIds = array_values(array_filter(
                $studentIds,
                fn (int $id) => ! isset($byStudent[$id]),
            ));

            $statuses = array_values($byStudent);
            $responseStatus = 'pending';
            if ($studentIds === []) {
                $household = $formResponses->firstWhere('student_id', null);
                $responseStatus = $household?->status ?? 'pending';
            } elseif ($pendingStudentIds === []) {
                $unique = array_values(array_unique($statuses));
                $responseStatus = count($unique) === 1 ? $unique[0] : 'mixed';
            } elseif ($statuses !== []) {
                $responseStatus = 'partial';
            }

            return [
                ...$form->toArray(),
                'responses_by_student' => (object) $byStudent,
                'pending_student_ids' => $pendingStudentIds,
                'response_status' => $responseStatus,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function respond(Request $request, int $formId)
    {
        $parent = $request->user();
        $data = $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
            'student_ids' => 'nullable|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'status' => 'nullable|string|in:approved,declined',
            'notes' => 'nullable|string|max:2000',
            'responses' => 'nullable|array|min:1',
            'responses.*.student_id' => 'required|integer|exists:students,id',
            'responses.*.status' => 'required|string|in:approved,declined',
        ]);

        ConsentForm::where('school_id', $parent->school_id)->findOrFail($formId);

        /** @var array<int, array{student_id: int, status: string}> $decisions */
        $decisions = [];

        if (! empty($data['responses'])) {
            foreach ($data['responses'] as $row) {
                $studentId = (int) $row['student_id'];
                $decisions[$studentId] = [
                    'student_id' => $studentId,
                    'status' => $row['status'],
                ];
            }
        } else {
            $status = $data['status'] ?? null;
            if (! $status) {
                throw ValidationException::withMessages([
                    'status' => ['Choose approve or decline for each child, or provide a status.'],
                ]);
            }

            $studentIds = collect($data['student_ids'] ?? [])
                ->when(
                    empty($data['student_ids'] ?? []) && ! empty($data['student_id']),
                    fn ($c) => $c->push((int) $data['student_id']),
                )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $accessibleIds = $this->parentAccess->accessibleStudentIds($parent)->map(fn ($id) => (int) $id)->all();

            if ($accessibleIds !== [] && $studentIds === []) {
                throw ValidationException::withMessages([
                    'student_ids' => ['Select at least one child for this consent response.'],
                ]);
            }

            if ($studentIds === []) {
                $decisions[0] = ['student_id' => 0, 'status' => $status];
            } else {
                foreach ($studentIds as $studentId) {
                    $decisions[$studentId] = [
                        'student_id' => $studentId,
                        'status' => $status,
                    ];
                }
            }
        }

        if ($decisions === []) {
            throw ValidationException::withMessages([
                'responses' => ['Choose approve or decline for at least one child.'],
            ]);
        }

        foreach ($decisions as $decision) {
            if ($decision['student_id'] > 0) {
                $this->parentAccess->assertCanAccessStudent($parent, $decision['student_id']);
            }
        }

        $responses = DB::transaction(function () use ($formId, $parent, $decisions, $data) {
            return collect($decisions)->map(function (array $decision) use ($formId, $parent, $data) {
                $studentId = $decision['student_id'] > 0 ? $decision['student_id'] : null;

                return ConsentResponse::updateOrCreate(
                    [
                        'form_id' => $formId,
                        'parent_user_id' => $parent->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $decision['status'],
                        'notes' => $data['notes'] ?? null,
                        'responded_at' => now(),
                    ],
                );
            });
        });

        $approved = collect($decisions)->where('status', 'approved')->count();
        $declined = collect($decisions)->where('status', 'declined')->count();
        $message = match (true) {
            $approved > 0 && $declined > 0 => "Saved: {$approved} approved, {$declined} declined.",
            $declined > 0 => $declined > 1
                ? "Declined for {$declined} children."
                : 'Consent declined.',
            $approved > 1 => "Approved for {$approved} children.",
            default => 'Consent response saved.',
        };

        return response()->json([
            'data' => $responses->values(),
            'message' => $message,
        ], 201);
    }
}
