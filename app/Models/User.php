<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Concerns\Auditable;
use App\Models\Scopes\UserSchoolScope;
use App\Services\PermissionService;
use App\Services\StudentResolutionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Ai\Concerns\HasConversations;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, HasApiTokens, HasConversations, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'permission_ids',
        'status',
        'platform_terms_version',
        'platform_terms_accepted_at',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'first_name',
        'last_name',
        'school_id',
        'avatar_url',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new UserSchoolScope());
    }

    public function scopeWithoutSchoolScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserSchoolScope::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolUserMembership::class);
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user_memberships')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function reportTemplatesCreated(): HasMany
    {
        return $this->hasMany(ReportTemplate::class, 'created_by');
    }

    /**
     * Get students associated with this user (when user is a parent)
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relationship', 'is_primary', 'school_id')
            ->withTimestamps();
    }

    public function children(): BelongsToMany
    {
        return $this->students();
    }

    public function leaveRequestsReviewed(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'reviewed_by');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'date_of_birth' => 'date',
            'two_factor_confirmed_at' => 'datetime',
            'platform_terms_accepted_at' => 'datetime',
            'role' => UserRole::class,
            'permission_ids' => 'array',
        ];
    }

    public function clearMustChangePassword(): void
    {
        if (! $this->must_change_password) {
            return;
        }

        $this->forceFill(['must_change_password' => false])->save();
    }

    public function hasAcceptedCurrentPlatformTerms(): bool
    {
        $currentVersion = (string) config('platform_terms.version');

        return $this->platform_terms_accepted_at !== null
            && (string) $this->platform_terms_version === $currentVersion;
    }

    public function acceptCurrentPlatformTerms(): void
    {
        $this->forceFill([
            'platform_terms_version' => (string) config('platform_terms.version'),
            'platform_terms_accepted_at' => now(),
        ])->save();
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, ['inactive', 'suspended', 'disabled'], true);
    }

    public function roleValue(): string
    {
        return $this->role instanceof UserRole ? $this->role->apiValue() : (string) $this->role;
    }

    /** Profile photo from users.avatar_url or linked student record. */
    public function resolveAvatarUrl(): ?string
    {
        if (filled($this->avatar_url)) {
            return $this->avatar_url;
        }

        if ($this->roleValue() !== UserRole::Student->apiValue()) {
            return null;
        }

        $student = $this->relationLoaded('student')
            ? $this->student
            : app(StudentResolutionService::class)->resolveForUser($this, persistLink: false);

        return filled($student?->photo_url) ? $student->photo_url : null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function hasPermission(string $slug): bool
    {
        return app(PermissionService::class)->hasPermission($this, $slug);
    }

    public function hasCapability(string $capability): bool
    {
        return app(PermissionService::class)->hasCapability($this, $capability);
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        return app(PermissionService::class)->permissionSlugsForUser($this);
    }
}
