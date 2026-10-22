<?php

namespace App\Console\Commands;

use App\Services\Messaging\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppCommand extends Command
{
    protected $signature = 'whatsapp:test
                            {phone : E.164 phone number, e.g. +263771234567}
                            {--message= : Plain-text body (ignored when --template is set)}
                            {--template : Force Twilio content template mode}';

    protected $description = 'Send a test WhatsApp message via the configured provider';

    public function handle(WhatsAppService $whatsapp): int
    {
        $phone = (string) $this->argument('phone');
        $message = (string) ($this->option('message') ?: 'School ERP WhatsApp test — '.now()->toDateTimeString());

        if ($this->option('template')) {
            config(['services.whatsapp.use_template' => true]);
        }

        $enabled = (bool) config('services.whatsapp.enabled', false);
        $provider = (string) config('services.whatsapp.provider', 'log');

        $this->line("Provider: {$provider}");
        $this->line('Enabled: '.($enabled ? 'yes' : 'no'));
        $this->info("Sending to {$phone}…");

        $sent = $whatsapp->send($phone, $message);

        if ($sent) {
            $this->info('WhatsApp message sent successfully.');

            return self::SUCCESS;
        }

        $this->error('WhatsApp message was not sent. Check logs and .env configuration.');

        return self::FAILURE;
    }
}
