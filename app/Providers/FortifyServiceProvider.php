<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\ApiLockoutResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LockoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LockoutResponse::class, ApiLockoutResponse::class);
    }

    public function boot(): void
    {
        $this->configurePasswordDefaults();

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            // School SPA dashboards fire many parallel widget/list calls; 60/min is too low.
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('assistant', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Laravel Breeze / Fortify starter-kit password defaults.
     */
    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers();

            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }
}
