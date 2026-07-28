<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS via the configured provider.
     */
    public function send(string $phone, string $message): bool
    {
        $enabled = (bool) config('services.sms.enabled', false);
        if (! $enabled) {
            Log::info("SMS disabled - would send to {$phone}: {$message}");

            return false;
        }

        $provider = config('services.sms.provider', 'log');

        return match ($provider) {
            'africastalking' => $this->sendViaAfricasTalking($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            default => $this->sendViaLog($phone, $message),
        };
    }

    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("SMS would be sent to {$phone}: {$message}");

        return true;
    }

    protected function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = config('services.sms.twilio_sid') ?: config('services.whatsapp.twilio_sid');
        $token = config('services.sms.twilio_token') ?: config('services.whatsapp.twilio_token');
        $from = config('services.sms.twilio_from');

        if (! $sid || ! $token || ! $from) {
            Log::warning('Twilio SMS credentials are not configured.');

            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $this->normalizePhone($phone),
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('Twilio SMS send failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        Log::info("SMS sent to {$phone} via Twilio");

        return true;
    }

    protected function sendViaAfricasTalking(string $phone, string $message): bool
    {
        $username = config('services.sms.africastalking_username');
        $apiKey = config('services.sms.africastalking_api_key');
        $from = config('services.sms.africastalking_from');

        if (! $username || ! $apiKey) {
            Log::warning("Africa's Talking SMS credentials are not configured.");

            return false;
        }

        $payload = [
            'username' => $username,
            'to' => $this->normalizePhone($phone),
            'message' => $message,
        ];

        if ($from) {
            $payload['from'] = $from;
        }

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', $payload);

        if (! $response->successful()) {
            Log::error("Africa's Talking SMS send failed", [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        Log::info("SMS sent to {$phone} via Africa's Talking");

        return true;
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/[^\d+]/', '', $phone) ?: $phone;
    }
}
