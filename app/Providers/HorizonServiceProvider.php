<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * SuperAdmin only (local environments also allow via Horizon defaults).
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?User $user = null) {
            return $user?->role === UserRole::SuperAdmin;
        });
    }
}
