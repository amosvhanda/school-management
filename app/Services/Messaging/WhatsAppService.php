<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message via the configured (or school-overridden) provider.
     *
     * @param  array<string, string>|null  $contentVariables
     * @param  array<string, mixed>|null  $config
     */
    public function send(string $phone, string $message, ?array $contentVariables = null, ?array $config = null): bool
    {
        $config ??= [
            'enabled' => (bool) config('services.whatsapp.enabled', false),
            'provider' => config('services.whatsapp.provider', 'log'),
            'twilio_sid' => config('services.whatsapp.twilio_sid'),
            'twilio_token' => config('services.whatsapp.twilio_token'),
            'twilio_from' => config('services.whatsapp.twilio_from'),
            'twilio_content_sid' => config('services.whatsapp.twilio_content_sid'),
            'twilio_content_variables' => config('services.whatsapp.twilio_content_variables'),
            'use_template' => (bool) config('services.whatsapp.use_template', false),
        ];

        $enabled = (bool) ($config['enabled'] ?? false);
        if (! $enabled) {
            Log::info("WhatsApp disabled - would send to {$phone}: {$message}");

            return false;
        }

        $provider = (string) ($config['provider'] ?? 'log');

        return match ($provider) {
            'twilio' => $this->sendViaTwilio($phone, $message, $contentVariables, $config),
            default => $this->sendViaLog($phone, $message),
        };
    }

    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("WhatsApp would be sent to {$phone}: {$message}");

        return true;
    }

    /**
     * @param  array<string, string>|null  $contentVariables
     * @param  array<string, mixed>  $config
     */
    protected function sendViaTwilio(string $phone, string $message, ?array $contentVariables, array $config): bool
    {
        $sid = $config['twilio_sid'] ?? null;
        $token = $config['twilio_token'] ?? null;
        $from = $config['twilio_from'] ?? null;

        if (! $sid || ! $token || ! $from) {
            Log::warning('Twilio WhatsApp credentials are not configured.');

            return false;
        }

        $payload = [
            'From' => $this->normalizeWhatsAppAddress($from),
            'To' => $this->normalizeWhatsAppAddress($phone),
        ];

        $contentSid = $config['twilio_content_sid'] ?? null;
        $useTemplate = (bool) ($config['use_template'] ?? false);

        if ($useTemplate && $contentSid) {
            $variables = $contentVariables ?? $this->defaultContentVariables($message, $config);
            $payload['ContentSid'] = $contentSid;
            $payload['ContentVariables'] = json_encode($variables, JSON_UNESCAPED_UNICODE);
        } else {
            $payload['Body'] = $message;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);

        if (! $response->successful()) {
            Log::error('Twilio WhatsApp send failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        Log::info("WhatsApp sent to {$phone} via Twilio", [
            'sid' => $response->json('sid'),
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function defaultContentVariables(string $message, array $config = []): array
    {
        $configured = $config['twilio_content_variables'] ?? null;
        if (is_string($configured) && $configured !== '') {
            $decoded = json_decode($configured, true);
            if (is_array($decoded) && $decoded !== []) {
                return array_map('strval', $decoded);
            }
        }

        return ['1' => $message];
    }

    protected function normalizeWhatsAppAddress(string $phone): string
    {
        $normalized = preg_replace('/[^\d+]/', '', $phone) ?: $phone;

        if (str_starts_with($phone, 'whatsapp:')) {
            return $phone;
        }

        if (! str_starts_with($normalized, '+')) {
            $normalized = '+'.$normalized;
        }

        return 'whatsapp:'.$normalized;
    }
}
