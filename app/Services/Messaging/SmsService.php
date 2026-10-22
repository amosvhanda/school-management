<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS via the configured (or school-overridden) provider.
     *
     * @param  array<string, mixed>|null  $config
     */
    public function send(string $phone, string $message, ?array $config = null): bool
    {
        $config ??= [
            'enabled' => (bool) config('services.sms.enabled', false),
            'provider' => config('services.sms.provider', 'log'),
            'twilio_sid' => config('services.sms.twilio_sid') ?: config('services.whatsapp.twilio_sid'),
            'twilio_token' => config('services.sms.twilio_token') ?: config('services.whatsapp.twilio_token'),
            'twilio_from' => config('services.sms.twilio_from'),
            'africastalking_username' => config('services.sms.africastalking_username'),
            'africastalking_api_key' => config('services.sms.africastalking_api_key'),
            'africastalking_from' => config('services.sms.africastalking_from'),
        ];

        $enabled = (bool) ($config['enabled'] ?? false);
        if (! $enabled) {
            Log::info("SMS disabled - would send to {$phone}: {$message}");

            return false;
        }

        $provider = (string) ($config['provider'] ?? 'log');

        return match ($provider) {
            'africastalking' => $this->sendViaAfricasTalking($phone, $message, $config),
            'twilio' => $this->sendViaTwilio($phone, $message, $config),
            default => $this->sendViaLog($phone, $message),
        };
    }

    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("SMS would be sent to {$phone}: {$message}");

        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function sendViaTwilio(string $phone, string $message, array $config): bool
    {
        $sid = $config['twilio_sid'] ?? null;
        $token = $config['twilio_token'] ?? null;
        $from = $config['twilio_from'] ?? null;

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

    /**
     * @param  array<string, mixed>  $config
     */
    protected function sendViaAfricasTalking(string $phone, string $message, array $config): bool
    {
        $username = $config['africastalking_username'] ?? null;
        $apiKey = $config['africastalking_api_key'] ?? null;
        $from = $config['africastalking_from'] ?? null;

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
