<?php

namespace App\Services\Platform;

use App\Models\ApiClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExternalApiService
{
    public function createClient(int $schoolId, string $name, array $scopes): array
    {
        $clientId = 'cli_'.Str::random(16);
        $secret = Str::random(40);

        $client = ApiClient::create([
            'school_id' => $schoolId,
            'name' => $name,
            'client_id' => $clientId,
            'client_secret_hash' => Hash::make($secret),
            'scopes' => $scopes,
            'is_active' => true,
        ]);

        return [
            'client' => $client,
            'client_secret' => $secret,
        ];
    }

    public function authenticate(string $clientId, string $clientSecret): ?ApiClient
    {
        $client = ApiClient::where('client_id', $clientId)->where('is_active', true)->first();
        if (! $client || ! Hash::check($clientSecret, $client->client_secret_hash)) {
            return null;
        }

        $client->update(['last_used_at' => now()]);

        return $client;
    }

    public function issueAccessToken(ApiClient $client, int $ttlSeconds = 3600): array
    {
        $expiresAt = now()->addSeconds($ttlSeconds)->timestamp;
        $payload = rtrim(strtr(base64_encode(json_encode([
            'sub' => $client->client_id,
            'sid' => $client->school_id,
            'exp' => $expiresAt,
        ])), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', $payload, (string) config('app.key'));

        return [
            'access_token' => $payload.'.'.$signature,
            'token_type' => 'Bearer',
            'expires_in' => $ttlSeconds,
        ];
    }
}
