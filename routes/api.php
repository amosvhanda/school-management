<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Laravel 12 — versioned JSON API)
|--------------------------------------------------------------------------
|
| All endpoints live under /api/v1. Use Sanctum bearer tokens or SPA cookies.
|
*/

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
