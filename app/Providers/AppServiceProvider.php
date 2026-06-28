<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
    }
}
