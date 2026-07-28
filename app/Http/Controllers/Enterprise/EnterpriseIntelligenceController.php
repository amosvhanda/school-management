<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\AlumniRecord;
use App\Models\MessageCampaign;
use App\Models\Student;
use App\Services\Enterprise\CommandCenterService;
use App\Services\Enterprise\StudentIntelligenceService;
use App\Services\Platform\CommunicationHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnterpriseIntelligenceController extends Controller
{
    public function __construct(
        private StudentIntelligenceService $intelligence,
        private CommandCenterService $commandCenter,
        private CommunicationHubService $communication,
    ) {}

    public function commandCenter(Request $request)
    {
        return response()->json(['data' => $this->commandCenter->dashboard($request->user()->school_id)]);
    }

    public function admissionScore(Request $request)
    {
        $data = Validator::make($request->all(), [
            'applicant_name' => 'required|string',
            'academic_score' => 'required|numeric',
            'interview_score' => 'nullable|numeric',
            'enrollment_application_id' => 'nullable|integer',
        ])->validate();

        return response()->json(['data' => $this->intelligence->scoreAdmission($request->user()->school_id, $data)], 201);
    }

    public function studentProfile(Request $request, int $id)
    {
        Student::where('school_id', $request->user()->school_id)->findOrFail($id);

        return response()->json(['data' => $this->intelligence->profile($id)]);
    }

    public function studentTimeline(Request $request, int $id)
    {
        Student::where('school_id', $request->user()->school_id)->findOrFail($id);

        return response()->json(['data' => $this->intelligence->timeline($id, $request->user()->school_id)]);
    }

    public function recordTimelineEvent(Request $request, int $id)
    {
        Student::where('school_id', $request->user()->school_id)->findOrFail($id);
        $data = Validator::make($request->all(), [
            'event_type' => 'required|string',
            'title' => 'required|string',
            'description' => 'nullable|string',
        ])->validate();

        return response()->json([
            'data' => $this->intelligence->recordTimelineEvent($request->user()->school_id, array_merge($data, ['student_id' => $id])),
        ], 201);
    }

    public function earlyWarnings(Request $request)
    {
        return response()->json(['data' => $this->intelligence->earlyWarnings($request->user()->school_id)]);
    }

    public function alumni(Request $request)
    {
        return response()->json(['data' => AlumniRecord::where('school_id', $request->user()->school_id)->orderByDesc('graduation_year')->get()]);
    }

    public function storeAlumni(Request $request)
    {
        $data = Validator::make($request->all(), [
            'student_id' => 'nullable|integer|exists:students,id',
            'full_name' => 'required|string|max:255',
            'graduation_year' => 'required|integer|min:1900|max:'.(now()->year + 1),
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'current_occupation' => 'nullable|string|max:255',
        ])->validate();

        if (! empty($data['student_id'])) {
            Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);
        }

        return response()->json([
            'data' => $this->intelligence->createAlumni($request->user()->school_id, $data),
            'message' => 'Alumni record created',
        ], 201);
    }

    public function updateAlumni(Request $request, int $id)
    {
        $alumni = AlumniRecord::where('school_id', $request->user()->school_id)->findOrFail($id);
        $data = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'graduation_year' => 'sometimes|required|integer|min:1900|max:'.(now()->year + 1),
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'current_occupation' => 'nullable|string|max:255',
        ])->validate();

        return response()->json([
            'data' => $this->intelligence->updateAlumni($alumni, $data),
            'message' => 'Alumni record updated',
        ]);
    }

    public function destroyAlumni(Request $request, int $id)
    {
        $alumni = AlumniRecord::where('school_id', $request->user()->school_id)->findOrFail($id);
        $alumni->delete();

        return response()->json(['message' => 'Alumni record deleted']);
    }

    public function recordAlumniEngagement(Request $request, int $id)
    {
        $alumni = AlumniRecord::where('school_id', $request->user()->school_id)->findOrFail($id);
        $data = Validator::make($request->all(), [
            'type' => 'required|string|max:100',
            'note' => 'nullable|string|max:1000',
        ])->validate();

        return response()->json([
            'data' => $this->intelligence->recordEngagement($alumni, $data['type'], $data['note'] ?? null),
            'message' => 'Engagement recorded',
        ], 201);
    }

    public function registerAlumni(Request $request, int $studentId)
    {
        $student = Student::where('school_id', $request->user()->school_id)->findOrFail($studentId);
        $data = Validator::make($request->all(), [
            'graduation_year' => 'nullable|integer|min:1900|max:'.(now()->year + 1),
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'current_occupation' => 'nullable|string|max:255',
        ])->validate();

        return response()->json(['data' => $this->intelligence->registerAlumni($request->user()->school_id, $student, $data)], 201);
    }

    public function campaigns(Request $request)
    {
        return response()->json(['data' => MessageCampaign::where('school_id', $request->user()->school_id)->orderByDesc('created_at')->get()]);
    }

    public function storeCampaign(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string',
            'channels' => 'required|array',
            'audience_filter' => 'required|array',
            'message_body' => 'required|string',
            'scheduled_at' => 'nullable|date',
        ])->validate();

        $campaign = MessageCampaign::create(array_merge($data, [
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
            'status' => $data['scheduled_at'] ? 'scheduled' : 'draft',
        ]));

        return response()->json(['data' => $campaign], 201);
    }

    public function sendCampaign(Request $request, int $id)
    {
        $campaign = MessageCampaign::where('school_id', $request->user()->school_id)->findOrFail($id);
        $recipientIds = $campaign->audience_filter['recipient_ids'] ?? [];

        if ($recipientIds) {
            $this->communication->send($request->user(), [
                'subject' => $campaign->name,
                'body' => $campaign->message_body,
                'channels' => $campaign->channels,
                'recipient_ids' => $recipientIds,
            ]);
        }

        $campaign->update(['status' => 'sent', 'sent_at' => now()]);

        return response()->json(['data' => $campaign->fresh(), 'message' => 'Campaign sent']);
    }
}
