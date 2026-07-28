<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message via the configured provider.
     */
    public function send(string $phone, string $message): bool
    {
        $enabled = (bool) config('services.whatsapp.enabled', false);
        if (! $enabled) {
            Log::info("WhatsApp disabled - would send to {$phone}: {$message}");

            return false;
        }

        $provider = config('services.whatsapp.provider', 'log');

        return match ($provider) {
            'twilio' => $this->sendViaTwilio($phone, $message),
            default => $this->sendViaLog($phone, $message),
        };
    }

    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("WhatsApp would be sent to {$phone}: {$message}");

        return true;
    }

    protected function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = config('services.whatsapp.twilio_sid');
        $token = config('services.whatsapp.twilio_token');
        $from = config('services.whatsapp.twilio_from');

        if (! $sid || ! $token || ! $from) {
            Log::warning('Twilio WhatsApp credentials are not configured.');

            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => 'whatsapp:'.$this->normalizePhone($phone),
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('Twilio WhatsApp send failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        Log::info("WhatsApp sent to {$phone} via Twilio");

        return true;
    }

    protected function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^\d+]/', '', $phone) ?: $phone;

        return str_starts_with($normalized, 'whatsapp:') ? $normalized : 'whatsapp:'.$normalized;
    }
}
