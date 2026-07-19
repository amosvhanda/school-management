<?php

namespace App\Http\Controllers;

use App\Models\ConsentForm;
use App\Models\ConsentResponse;
use App\Services\ParentAccessService;
use Illuminate\Http\Request;

class ConsentFormController extends Controller
{
    public function __construct(private ParentAccessService $parentAccess) {}

    public function index(Request $request)
    {
        return response()->json([
            'data' => ConsentForm::where('school_id', $request->user()->school_id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
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
        $forms = ConsentForm::where('school_id', $parent->school_id)
            ->where('status', 'active')
            ->whereIn('target_audience', ['parents', 'all'])
            ->get();

        return response()->json(['data' => $forms]);
    }

    public function respond(Request $request, int $formId)
    {
        $parent = $request->user();
        $data = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'status' => 'required|string|in:approved,declined',
            'notes' => 'nullable|string',
        ]);

        if ($data['student_id'] ?? null) {
            $this->parentAccess->assertCanAccessStudent($parent, (int) $data['student_id']);
        }

        ConsentForm::where('school_id', $parent->school_id)->findOrFail($formId);

        $response = ConsentResponse::updateOrCreate(
            [
                'form_id' => $formId,
                'parent_user_id' => $parent->id,
                'student_id' => $data['student_id'] ?? null,
            ],
            [
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'responded_at' => now(),
            ],
        );

        return response()->json(['data' => $response], 201);
    }
}
