<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('license:expire')->daily();
Schedule::command('notifications:process --limit=100')->everyFiveMinutes();
Schedule::command('fees:apply-penalties')->dailyAt('01:15');
Schedule::command('workflows:escalate')->hourly();
Schedule::command('data:archive-expired')->dailyAt('02:30');
