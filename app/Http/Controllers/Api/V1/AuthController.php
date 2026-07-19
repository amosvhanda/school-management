<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Auth\SecureAuthenticationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private SecureAuthenticationService $authService) {}

    public function login(LoginRequest $request)
    {
        $user = $this->authService->authenticate(
            $request,
            $request->email,
            $request->password,
            $request->role
        );

        $token = $this->authService->createApiToken($user);
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new UserResource($user))->resolve(),
                $context
            ),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $this->authService->revokeCurrentToken($request->user());

        return $this->success(message: 'Logged out successfully');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new UserResource($user))->resolve(),
                $context
            ),
        ]);
    }

    public function platformTerms()
    {
        return $this->success([
            'version' => (string) config('platform_terms.version'),
            'title' => (string) config('platform_terms.title'),
            'summary' => (string) config('platform_terms.summary'),
            'content' => (string) config('platform_terms.content'),
        ]);
    }

    public function acceptPlatformTerms(Request $request)
    {
        $data = $request->validate([
            'accepted' => ['required', 'accepted'],
            'version' => ['required', 'string'],
        ]);

        $currentVersion = (string) config('platform_terms.version');
        if ($data['version'] !== $currentVersion) {
            throw ValidationException::withMessages([
                'version' => ['These terms have been updated. Refresh and accept the latest version.'],
            ]);
        }

        $user = $request->user();
        $user->acceptCurrentPlatformTerms();
        $context = $this->authService->resolveRoleContext($user->fresh());

        return $this->success([
            'user' => array_merge(
                (new UserResource($user->fresh()))->resolve(),
                $context
            ),
        ], 'Platform terms accepted');
    }

    public function changePassword(ChangePasswordRequest $request, UpdateUserPassword $updater)
    {
        $updater->update($request->user(), $request->only([
            'current_password',
            'password',
            'password_confirmation',
        ]));

        return $this->success(message: 'Password changed successfully. Please log in again.');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::broker(config('fortify.passwords'))->sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(message: __($status));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::broker(config('fortify.passwords'))->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(message: __($status));
    }
}
