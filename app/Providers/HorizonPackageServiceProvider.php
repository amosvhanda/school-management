<?php

namespace App\Providers;

use Laravel\Horizon\Console\WorkCommand;
use Laravel\Horizon\HorizonServiceProvider as BaseHorizonServiceProvider;

/**
 * Laravel 13 blocks vendor packages from registering DevCommands.
 * Horizon's default provider calls Horizon::registerDevCommands() from vendor,
 * which throws during package:discover. We skip that here and register the
 * artisan "dev" command from AppServiceProvider (application code) instead.
 */
class HorizonPackageServiceProvider extends BaseHorizonServiceProvider
{
    public function register(): void
    {
        if (! defined('HORIZON_PATH')) {
            define('HORIZON_PATH', realpath(base_path('vendor/laravel/horizon')));
        }

        $this->app->bind(WorkCommand::class, function ($app) {
            return new WorkCommand($app['queue.worker'], $app['cache.store']);
        });

        $this->configure();
        $this->registerServices();
        $this->registerQueueConnectors();
    }
}
