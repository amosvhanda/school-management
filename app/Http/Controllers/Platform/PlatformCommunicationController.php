<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ResolvesPlatformSchoolScope;
use App\Http\Requests\Platform\SendHubMessageRequest;
use App\Models\HubMessageDelivery;
use App\Models\HubMessage; // Assuming this model holds root communication details
use App\Services\Platform\CommunicationHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlatformCommunicationController extends Controller
{
    use ResolvesPlatformSchoolScope;

    public function __construct(private CommunicationHubService $hub) {}

    /**
     * Dispatch an omni-channel announcement across the platform
     */
    public function send(SendHubMessageRequest $request): JsonResponse
    {
        // Validation rules are securely abstracted inside the form request
        $data = $request->validated();
        $data['school_id'] = $this->requirePlatformSchoolId($request);

        // Tip: Ensure this service method pushes jobs into a background queue (e.g., dispatch(new SendMessageJob))
        $message = $this->hub->send($request->user(), $data);

        return response()->json([
            'data' => $message,
            'message' => 'Message successfully queued across designated communication channels'
        ], 201);
    }

    /**
     * View delivery metrics and status logs for sent messages
     */
    public function tracking(Request $request): JsonResponse
    {
        $schoolId = $this->platformSchoolId($request);
        $messageId = $request->integer('message_id') ?: null;

        // Secure parameter input boundary checking
        if ($messageId) {
            $messageExists = HubMessage::query()
                ->where('id', $messageId)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->exists();

            if (! $messageExists) {
                throw ValidationException::withMessages([
                    'message_id' => 'The requested message tracking record does not exist or belongs to another tenant.'
                ]);
            }
        }

        return response()->json([
            'data' => $this->hub->trackingForSchool($schoolId, $messageId),
        ]);
    }

    /**
     * Explicitly acknowledge and flag an individual delivery receipt as read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->platformSchoolId($request);

        // Lock down cross-tenant read updates (super admin can resolve across schools)
        $delivery = HubMessageDelivery::whereHas('message', function ($query) use ($schoolId) {
            $query->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        })->findOrFail($id);

        return response()->json([
            'data' => $this->hub->markRead($delivery, $request->user()),
            'message' => 'Message marked as read',
        ]);
    }
}
