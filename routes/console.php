<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$onOneServer = function ($event) {
    return config('cache.default') === 'redis'
        ? $event->onOneServer()
        : $event;
};

$onOneServer(Schedule::command('license:expire')->daily()->withoutOverlapping());
$onOneServer(Schedule::command('notifications:process --limit=100')->everyFiveMinutes()->withoutOverlapping());
$onOneServer(Schedule::command('fees:apply-penalties')->dailyAt('01:15')->withoutOverlapping());
$onOneServer(Schedule::command('hr:sync-teacher-leave-status')->dailyAt('00:20')->withoutOverlapping());
$onOneServer(Schedule::command('workflows:escalate')->hourly()->withoutOverlapping());
$onOneServer(Schedule::command('webhooks:retry-failed --limit=50')->everyFifteenMinutes()->withoutOverlapping());
$onOneServer(Schedule::command('data:archive-expired')->dailyAt('02:30')->withoutOverlapping());
$onOneServer(Schedule::command('queue:prune-failed --hours=168')->daily()->withoutOverlapping());

if (config('queue.default') === 'redis') {
    $onOneServer(Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping());
}
