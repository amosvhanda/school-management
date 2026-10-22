<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonPackageServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
];

// Dev dependency — skip when composer install --no-dev (production).
if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
