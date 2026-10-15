<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PaymentsStagingInfoCommand extends Command
{
    protected $signature = 'payments:staging-info';

    protected $description = 'Print Paynow callback URLs and staging checklist hints';

    public function handle(): int
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $resultUrl = config('services.paynow.result_url')
            ?: $appUrl.'/api/v1/webhooks/payments/paynow';
        $sandboxCheckout = $appUrl.'/api/v1/webhooks/payments/paynow/sandbox-checkout';
        $statusPoll = $appUrl.'/api/v1/platform/payments/status/{reference}';

        $this->info('Paynow & payments staging');
        $this->line('APP_URL: '.($appUrl ?: '(not set)'));
        $this->line('PAYMENTS_MODE: '.config('services.payments.mode'));
        $this->line('Paynow result URL (register in Paynow dashboard): '.$resultUrl);
        $this->line('Sandbox checkout URL: '.$sandboxCheckout);
        $this->line('Payment status poll: '.$statusPoll);
        $this->newLine();
        $this->comment('Per-school credentials: POST /api/v1/platform/payment-gateways with integration_id + integration_key (encrypted at rest).');
        $this->comment('Enterprise outbound webhooks: POST /api/v1/enterprise/integrations/webhooks (secret returned once).');
        $this->comment('Queue worker: php artisan queue:work --queue=webhooks,notifications,default');
        $this->comment('Retry failed deliveries: php artisan webhooks:retry-failed');

        if ($appUrl === '' || str_contains($appUrl, 'localhost')) {
            $this->warn('APP_URL should be your public staging API URL before going live with Paynow.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
