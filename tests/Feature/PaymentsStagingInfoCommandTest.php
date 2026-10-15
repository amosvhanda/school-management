<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaymentsStagingInfoCommandTest extends TestCase
{
    public function test_staging_info_prints_paynow_urls(): void
    {
        config(['app.url' => 'https://api.staging.example']);

        $this->artisan('payments:staging-info')
            ->assertSuccessful()
            ->expectsOutputToContain('https://api.staging.example/api/v1/webhooks/payments/paynow');
    }

    public function test_staging_info_warns_when_app_url_is_localhost(): void
    {
        config(['app.url' => 'http://localhost']);

        $this->artisan('payments:staging-info')
            ->assertFailed()
            ->expectsOutputToContain('APP_URL should be your public staging API URL');
    }
}
