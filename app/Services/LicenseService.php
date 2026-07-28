<?php

namespace App\Services;

use App\Enums\LicenseKeyStatus;
use App\Enums\LicensePlanType;
use App\Models\LicenseKey;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LicenseService
{
    /**
     * @return array{license_key: string, record: LicenseKey}
     */
    public function generate(User $issuer, array $data): array
    {
        $planType = LicensePlanType::from($data['plan_type']);
        $durationMonths = $planType->durationMonths(
            isset($data['duration_months']) ? (int) $data['duration_months'] : null
        );

        if ($planType === LicensePlanType::Custom && ($durationMonths === null || $durationMonths < 1)) {
            throw ValidationException::withMessages([
                'duration_months' => ['Custom plans require duration_months of at least 1.'],
            ]);
        }

        $plainKey = $this->buildPlainKey();
        $currency = strtoupper((string) ($data['currency'] ?? config('license.currency', 'USD')));
        $amount = array_key_exists('amount', $data)
            ? (float) $data['amount']
            : (float) config("license.prices.{$planType->value}", 0);

        $record = LicenseKey::create([
            'key_prefix' => $this->extractPrefix($plainKey),
            'key_hash' => $this->hashKey($plainKey),
            'plan_type' => $planType,
            'duration_months' => $durationMonths,
            'amount' => $amount > 0 ? round($amount, 2) : null,
            'currency' => $currency,
            'status' => LicenseKeyStatus::Unused,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $issuer->id,
        ]);

        return [
            'license_key' => $plainKey,
            'record' => $record,
        ];
    }

    public function activate(School $school, string $plainKey): LicenseKey
    {
        $normalized = $this->normalizeKey($plainKey);
        $hash = $this->hashKey($normalized);

        $key = LicenseKey::query()
            ->where('key_hash', $hash)
            ->where('status', LicenseKeyStatus::Unused)
            ->first();

        if (! $key) {
            throw ValidationException::withMessages([
                'license_key' => ['Invalid or already used license key.'],
            ]);
        }

        return DB::transaction(function () use ($school, $key) {
            $expiresAt = $this->calculateExpiry($school, $key);

            $key->update([
                'school_id' => $school->id,
                'status' => LicenseKeyStatus::Active,
                'activated_at' => now(),
                'paid_at' => $key->paid_at ?? now(),
                'expires_at' => $expiresAt,
            ]);

            $this->syncSchoolLicense($school, $key->fresh());

            LicenseKey::query()
                ->where('school_id', $school->id)
                ->where('id', '!=', $key->id)
                ->where('status', LicenseKeyStatus::Active)
                ->update([
                    'status' => LicenseKeyStatus::Expired,
                ]);

            return $key->fresh(['school', 'creator']);
        });
    }

    public function revoke(LicenseKey $key): LicenseKey
    {
        $key->update([
            'status' => LicenseKeyStatus::Revoked,
            'revoked_at' => now(),
        ]);

        if ($key->school_id) {
            $school = School::find($key->school_id);
            if ($school && $school->license_status === 'active') {
                $school->update([
                    'license_status' => 'expired',
                ]);
            }
        }

        return $key->fresh();
    }

    public function status(School $school): array
    {
        $enforcement = (bool) config('license.enforcement', false);
        $state = $this->resolveLicenseState($school);

        $activeKey = LicenseKey::query()
            ->where('school_id', $school->id)
            ->where('status', LicenseKeyStatus::Active)
            ->latest('activated_at')
            ->first();

        return array_merge($state, [
            'enforcement' => $enforcement,
            'plan' => $school->license_plan,
            'expires_at' => $school->license_expires_at?->toIso8601String(),
            'active_key' => $activeKey ? [
                'id' => $activeKey->id,
                'plan_type' => $activeKey->plan_type->value,
                'activated_at' => $activeKey->activated_at?->toIso8601String(),
                'expires_at' => $activeKey->expires_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function isLicensed(School $school): bool
    {
        if (! config('license.enforcement', false)) {
            return true;
        }

        $state = $this->resolveLicenseState($school);

        return in_array($state['status'], ['active', 'grace'], true);
    }

    public function expireDueLicenses(): int
    {
        $graceDays = (int) config('license.grace_days', 7);
        $count = 0;

        School::query()
            ->where('license_status', 'active')
            ->whereNotNull('license_expires_at')
            ->where('license_expires_at', '<', now()->subDays($graceDays))
            ->chunkById(100, function ($schools) use (&$count) {
                foreach ($schools as $school) {
                    $school->update(['license_status' => 'expired']);
                    LicenseKey::query()
                        ->where('school_id', $school->id)
                        ->where('status', LicenseKeyStatus::Active)
                        ->update(['status' => LicenseKeyStatus::Expired]);
                    $count++;
                }
            });

        return $count;
    }

    public function listKeys(array $filters = []): Collection
    {
        $query = LicenseKey::query()->with(['school:id,name,code', 'creator:id,name,email']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }

        if (! empty($filters['unassigned'])) {
            $query->whereNull('school_id');
        }

        return $query->orderByDesc('created_at')->limit(200)->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSchools(array $filters = []): array
    {
        $query = School::query()->withCount('users')->orderBy('name');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['license_status'])) {
            match ($filters['license_status']) {
                'licensed', 'active' => $query->where('license_status', 'active'),
                'unlicensed', 'none' => $query->where(function ($builder) {
                    $builder->whereNull('license_status')
                        ->orWhere('license_status', 'none');
                }),
                'expired' => $query->where('license_status', 'expired'),
                'grace' => $query->where('license_status', 'grace'),
                default => $query->where('license_status', $filters['license_status']),
            };
        }

        return $query->limit(500)->get()
            ->map(fn (School $school) => $this->formatSchoolLicenseRow($school))
            ->all();
    }

    /**
     * @return array{
     *   schools: array<string, int>,
     *   keys: array<string, int>,
     *   revenue: array{
     *     currency: string,
     *     mtd: float,
     *     ytd: float,
     *     recognized_total: float,
     *     by_plan: array<string, float>
     *   }
     * }
     */
    public function platformSummary(): array
    {
        $keyCounts = LicenseKey::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $currency = strtoupper((string) config('license.currency', 'USD'));
        $recognized = LicenseKey::query()
            ->whereNotNull('paid_at')
            ->where('amount', '>', 0)
            ->whereIn('status', [
                LicenseKeyStatus::Active->value,
                LicenseKeyStatus::Expired->value,
            ]);

        $mtd = (clone $recognized)
            ->where('paid_at', '>=', now()->copy()->startOfMonth())
            ->sum('amount');
        $ytd = (clone $recognized)
            ->where('paid_at', '>=', now()->copy()->startOfYear())
            ->sum('amount');
        $total = (clone $recognized)->sum('amount');

        $byPlan = LicenseKey::query()
            ->selectRaw('plan_type, coalesce(sum(amount), 0) as total')
            ->whereNotNull('paid_at')
            ->where('amount', '>', 0)
            ->whereIn('status', [
                LicenseKeyStatus::Active->value,
                LicenseKeyStatus::Expired->value,
            ])
            ->groupBy('plan_type')
            ->pluck('total', 'plan_type')
            ->map(fn ($value) => round((float) $value, 2))
            ->all();

        return [
            'schools' => [
                'total' => School::count(),
                'licensed' => School::where('license_status', 'active')->count(),
                'unlicensed' => School::query()
                    ->where(function ($query) {
                        $query->whereNull('license_status')
                            ->orWhere('license_status', 'none');
                    })
                    ->count(),
                'expired' => School::where('license_status', 'expired')->count(),
            ],
            'keys' => [
                'total' => LicenseKey::count(),
                'unused' => (int) ($keyCounts[LicenseKeyStatus::Unused->value] ?? 0),
                'active' => (int) ($keyCounts[LicenseKeyStatus::Active->value] ?? 0),
                'expired' => (int) ($keyCounts[LicenseKeyStatus::Expired->value] ?? 0),
                'revoked' => (int) ($keyCounts[LicenseKeyStatus::Revoked->value] ?? 0),
            ],
            'revenue' => [
                'currency' => $currency,
                'mtd' => round((float) $mtd, 2),
                'ytd' => round((float) $ytd, 2),
                'recognized_total' => round((float) $total, 2),
                'by_plan' => $byPlan,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatSchoolLicenseRow(School $school): array
    {
        $activeKey = LicenseKey::query()
            ->where('school_id', $school->id)
            ->where('status', LicenseKeyStatus::Active)
            ->latest('activated_at')
            ->first();

        $state = $this->resolveLicenseState($school);

        return [
            'id' => $school->id,
            'name' => $school->name,
            'code' => $school->code,
            'email' => $school->email,
            'phone' => $school->phone,
            'status' => $school->status,
            'license_status' => $school->license_status ?? 'none',
            'license_plan' => $school->license_plan,
            'license_expires_at' => $school->license_expires_at?->toIso8601String(),
            'users_count' => (int) $school->users_count,
            'license_state' => $state,
            'active_key' => $activeKey ? [
                'id' => $activeKey->id,
                'key_prefix' => $activeKey->key_prefix,
                'plan_type' => $activeKey->plan_type->value,
                'status' => $activeKey->status->value,
                'activated_at' => $activeKey->activated_at?->toIso8601String(),
                'expires_at' => $activeKey->expires_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @return array{status: string, days_remaining: ?int, grace_ends_at: ?string, message: string}
     */
    public function resolveLicenseState(School $school): array
    {
        if ($school->license_status === 'none' || $school->license_status === null) {
            return [
                'status' => 'none',
                'days_remaining' => null,
                'grace_ends_at' => null,
                'message' => 'No license activated. Enter your access key to continue.',
            ];
        }

        if ($school->license_plan === LicensePlanType::Lifetime->value || $school->license_expires_at === null) {
            if ($school->license_status === 'active') {
                return [
                    'status' => 'active',
                    'days_remaining' => null,
                    'grace_ends_at' => null,
                    'message' => 'Lifetime license active.',
                ];
            }
        }

        if ($school->license_expires_at && $school->license_expires_at->isFuture()) {
            return [
                'status' => 'active',
                'days_remaining' => (int) now()->diffInDays($school->license_expires_at, false),
                'grace_ends_at' => null,
                'message' => 'License active.',
            ];
        }

        $graceDays = (int) config('license.grace_days', 7);
        $graceEndsAt = $school->license_expires_at?->copy()->addDays($graceDays);

        if ($graceEndsAt && $graceEndsAt->isFuture()) {
            return [
                'status' => 'grace',
                'days_remaining' => 0,
                'grace_ends_at' => $graceEndsAt->toIso8601String(),
                'message' => 'License expired. Renew before grace period ends.',
            ];
        }

        return [
            'status' => 'expired',
            'days_remaining' => 0,
            'grace_ends_at' => null,
            'message' => 'License expired. Enter a renewal key to restore access.',
        ];
    }

    protected function calculateExpiry(School $school, LicenseKey $key): ?Carbon
    {
        if ($key->isLifetime()) {
            return null;
        }

        $months = $key->duration_months ?? 1;
        $base = ($school->license_expires_at && $school->license_expires_at->isFuture())
            ? $school->license_expires_at
            : now();

        return $base->copy()->addMonths($months);
    }

    protected function syncSchoolLicense(School $school, LicenseKey $key): void
    {
        $school->update([
            'license_plan' => $key->plan_type->value,
            'license_status' => 'active',
            'license_expires_at' => $key->expires_at,
        ]);
    }

    protected function buildPlainKey(): string
    {
        $prefix = config('license.key_prefix', 'SKERP');

        do {
            $plainKey = sprintf(
                '%s-%s-%s',
                $prefix,
                strtoupper(Str::random(4)),
                strtoupper(Str::random(8)),
            );
        } while (LicenseKey::where('key_hash', $this->hashKey($plainKey))->exists());

        return $plainKey;
    }

    protected function normalizeKey(string $plainKey): string
    {
        return strtoupper(trim($plainKey));
    }

    protected function hashKey(string $plainKey): string
    {
        return hash('sha256', $this->normalizeKey($plainKey));
    }

    protected function extractPrefix(string $plainKey): string
    {
        return substr($this->normalizeKey($plainKey), 0, 16);
    }
}
