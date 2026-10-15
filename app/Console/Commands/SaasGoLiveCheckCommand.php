<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SaasGoLiveCheckCommand extends Command
{
    protected $signature = 'saas:go-live-check {--strict : Fail on warnings even outside production}';

    protected $description = 'Verify shared-DB SaaS production readiness (config, license, queues, tenancy, payments)';

    public function handle(): int
    {
        $strict = $this->option('strict') || app()->isProduction();
        $failed = false;

        $this->info('SaaS go-live check (shared-database tenancy)');
        $this->newLine();

        $queueDriver = (string) config('queue.default');
        $usesRedisQueue = $queueDriver === 'redis';
        $horizonInstalled = class_exists(\Laravel\Horizon\Horizon::class);
        $pulseInstalled = class_exists(\Laravel\Pulse\Pulse::class);

        $checks = [
            ['APP_KEY set', filled(config('app.key')), true],
            ['APP_DEBUG off in production', ! app()->isProduction() || config('app.debug') === false, true],
            ['License enforcement on in production', ! app()->isProduction() || (bool) config('license.enforcement'), true],
            ['Two-factor enforcement on in production', ! app()->isProduction() || (bool) config('security.require_two_factor'), true],
            ['Cache store ready for production', ! app()->isProduction() || config('cache.default') !== 'file', false],
            ['Queue not sync', $queueDriver !== 'sync', false],
            ['Redis queue when production', ! app()->isProduction() || $usesRedisQueue, false],
            ['Horizon installed for Redis queues', ! $usesRedisQueue || $horizonInstalled, false],
            ['Pulse installed', $pulseInstalled, false],
            ['APP_URL not localhost in production', ! app()->isProduction()
                || (! str_contains((string) config('app.url'), 'localhost')
                    && ! str_contains((string) config('app.url'), '127.0.0.1')), true],
            ['Central domains configured or non-production', ! empty(config('tenancy.central_domains')) || ! app()->isProduction(), false],
        ];

        foreach ($checks as [$label, $pass, $hard]) {
            if ($pass) {
                $this->line("<fg=green>✓</> {$label}");
                continue;
            }

            if ($hard || $strict) {
                $failed = true;
                $this->line("<fg=red>✗</> {$label}");
            } else {
                $this->line("<fg=yellow>!</> {$label} (warning)");
            }
        }

        $this->newLine();
        $this->comment('Operator follow-ups: wildcard DNS/TLS, Redis + Horizon workers, deploy horizon:terminate.');
        $this->comment('SMS/WhatsApp/Paynow are optional. See docs/deploy/production-readiness.md');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
