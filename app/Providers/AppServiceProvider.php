<?php

namespace App\Providers;

use App\Contracts\TeachingAssistant;
use App\Services\Ai\StubTeachingAssistant;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Teacher AI assistant. Swap the stub for a live LLM-backed driver
        // once a provider is configured.
        $this->app->bind(TeachingAssistant::class, StubTeachingAssistant::class);
    }

    public function boot(): void
    {
        // SQLite cannot handle concurrent cache writes on the same DB file (rate limiting, etc.).
        if (config('database.default') === 'sqlite' && config('cache.default') === 'database') {
            config(['cache.default' => 'file']);
        }

        Model::preventLazyLoading($this->app->isLocal() && ! $this->app->runningInConsole());
        Model::preventSilentlyDiscardingAttributes($this->app->isLocal() && ! $this->app->runningInConsole());

        if ($this->app->isProduction()) {
            DB::prohibitDestructiveCommands();
        }

        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)
            || ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://'))) {
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $user, string $token) {
            $frontend = (string) env('FRONTEND_URL', 'http://localhost:5173');
            $base = rtrim(explode(',', $frontend)[0] ?? 'http://localhost:5173', '/');
            $email = urlencode((string) ($user->email ?? ''));

            return "{$base}/reset-password?token={$token}&email={$email}";
        });
    }
}
