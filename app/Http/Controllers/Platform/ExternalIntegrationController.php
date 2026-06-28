<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\IssueIntegrationTokenRequest;
use App\Services\Platform\ExternalApiService;
use Illuminate\Http\JsonResponse;

class ExternalIntegrationController extends Controller
{
    public function __construct(private ExternalApiService $api) {}

    /**
     * Issue an API Access Token for external platform integrations
     */
    public function token(IssueIntegrationTokenRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 1. Authenticate using a cryptographically secure hash mechanism
        //    (Internal service should use Hash::check under the hood)
        $client = $this->api->authenticate($data['client_id'], $data['client_secret']);

        if (! $client) {
            return response()->json([
                'error' => 'invalid_client',
                'message' => 'Client authentication failed (invalid credentials).'
            ], 401);
        }

        // 2. Ensure the tenant/client profile is actively authorized to connect
        if (isset($client->is_active) && ! $client->is_active) {
            return response()->json([
                'error' => 'unauthorized_client',
                'message' => 'This integration application has been suspended.'
            ], 403);
        }

        // 3. Keep response clean and standard.
        //    Internal details like school_id and scopes should be encoded *inside* the token token itself.
        $tokenPayload = $this->api->issueAccessToken($client);

        return response()->json($tokenPayload);
    }
}
