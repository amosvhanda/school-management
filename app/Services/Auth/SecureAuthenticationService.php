<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LicenseService;
use App\Services\ParentAccessService;
use App\Services\PermissionService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\LoginRateLimiter;

class SecureAuthenticationService
{
    public function __construct(
        private LoginRateLimiter $loginRateLimiter,
        private ParentAccessService $parentAccess,
        private AuditService $auditService,
        private LicenseService $licenseService,
        private PermissionService $permissionService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function authenticate(Request $request, string $email, string $password, ?string $role = null): User
    {
        $this->ensureIsNotRateLimited($request);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->loginRateLimiter->increment($request);
            $this->auditService->logAuthEvent('failed_login', email: $email, failureReason: 'Invalid credentials');

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        if ($user->role !== UserRole::SuperAdmin && ! $user->school_id) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not associated with a school. Please contact support.'],
            ]);
        }

        if ($role && $user->roleValue() !== $role) {
            throw ValidationException::withMessages([
                'role' => ['Invalid role for this user.'],
            ]);
        }

        $this->loginRateLimiter->clear($request);

        $this->auditService->logAuthEvent('login', user: $user, tokenName: 'api-v1');

        return $user;
    }

    public function createApiToken(User $user, string $deviceName = 'api-v1'): string
    {
        $user->tokens()->where('name', $deviceName)->delete();

        return $user->createToken($deviceName, ['*'])->plainTextToken;
    }

    public function resolveRoleContext(User $user): array
    {
        $context = [
            'student_id' => $user->role === UserRole::Student
                ? Student::where('user_id', $user->id)->value('id')
                : null,
            'teacher_id' => $user->role === UserRole::Teacher
                ? Teacher::where('user_id', $user->id)->value('id')
                : null,
            'guardian_id' => null,
            'children' => [],
        ];

        if ($user->role === UserRole::Parent) {
            $context['guardian_id'] = Guardian::where('user_id', $user->id)->value('id');
            $context['children'] = Student::query()
                ->whereIn('id', $this->parentAccess->accessibleStudentIds($user))
                ->where('status', 'active')
                ->with('classModel:id,name')
                ->get()
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'fullName' => $student->full_name,
                    'studentNumber' => $student->student_number,
                    'class' => $student->classModel?->name ?? $student->class,
                ])
                ->values()
                ->all();
        }

        if ($user->school_id) {
            $school = School::find($user->school_id);
            if ($school) {
                $context['license'] = $this->licenseService->status($school);
            }
        }

        $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFromMixed($user->roleValue());
        if ($role) {
            $context['capabilities'] = $this->permissionService->resolveCapabilities($user);
            $context['permissions'] = $this->permissionService->permissionSlugsForUser($user);
        }

        return $context;
    }

    public function revokeCurrentToken(User $user): void
    {
        $this->auditService->logAuthEvent('logout', user: $user, tokenName: $user->currentAccessToken()?->name);
        $user->currentAccessToken()?->delete();
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! $this->loginRateLimiter->tooManyAttempts($request)) {
            return;
        }

        event(new Lockout($request));

        $seconds = $this->loginRateLimiter->availableIn($request);

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', ['seconds' => $seconds])],
        ])->status(429);
    }
}
