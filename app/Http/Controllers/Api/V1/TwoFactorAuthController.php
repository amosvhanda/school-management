<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\Auth\SecureAuthenticationService;
use App\Services\Tenancy\TenantSwitchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthController extends Controller
{
    public function __construct(
        private SecureAuthenticationService $authService,
        private TenantSwitchService $tenantSwitch,
        private TwoFactorAuthenticationProvider $provider,
    ) {}

    public function challenge(Request $request)
    {
        $data = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $userId = Cache::pull($this->challengeKey($data['challenge_token']));
        if (! $userId) {
            throw ValidationException::withMessages([
                'challenge_token' => ['This two-factor challenge has expired. Please sign in again.'],
            ]);
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Unable to verify two-factor authentication.'],
            ]);
        }

        $code = str_replace(' ', '', $data['code']);
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $valid = $this->provider->verify($secret, $code)
            || $this->validRecoveryCode($user, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        $token = $this->authService->createApiToken($user);
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new \App\Http\Resources\Api\V1\UserResource($user))->resolve(),
                $context,
                ['schools' => $this->tenantSwitch->availableSchools($user)->all()],
            ),
            'token' => $token,
        ], 'Login successful');
    }

    public function status(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success([
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'confirmed' => $user->two_factor_confirmed_at !== null,
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable)
    {
        /** @var User $user */
        $user = $request->user();
        $enable($user);

        $user->refresh();

        return $this->success([
            'enabled' => true,
            'confirmed' => false,
            'qr_code_svg' => $user->twoFactorQrCodeSvg(),
            'setup_key' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            'recovery_codes' => $user->recoveryCodes(),
        ], 'Two-factor authentication enabled. Confirm with an authenticator code.');
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $confirm($user, $data['code']);

        return $this->success([
            'enabled' => true,
            'confirmed' => true,
        ], 'Two-factor authentication confirmed.');
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $request->validate([
            'password' => ['required', 'current_password:sanctum'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $disable($user);

        return $this->success([
            'enabled' => false,
            'confirmed' => false,
        ], 'Two-factor authentication disabled.');
    }

    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generate)
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $generate($user);
        $user->refresh();

        return $this->success([
            'recovery_codes' => $user->recoveryCodes(),
        ], 'Recovery codes regenerated.');
    }

    public function issueChallengeToken(User $user): string
    {
        $token = Str::random(64);
        Cache::put($this->challengeKey($token), $user->id, now()->addMinutes(10));

        return $token;
    }

    private function challengeKey(string $token): string
    {
        return 'two_factor_challenge:'.$token;
    }

    private function validRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->recoveryCodes();
        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        return true;
    }
}
