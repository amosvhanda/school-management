<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message via the configured provider.
     *
     * @param  array<string, string>|null  $contentVariables  Twilio template variables (e.g. ["1" => "12/1", "2" => "3pm"])
     */
    public function send(string $phone, string $message, ?array $contentVariables = null): bool
    {
        $enabled = (bool) config('services.whatsapp.enabled', false);
        if (! $enabled) {
            Log::info("WhatsApp disabled - would send to {$phone}: {$message}");

            return false;
        }

        $provider = config('services.whatsapp.provider', 'log');

        return match ($provider) {
            'twilio' => $this->sendViaTwilio($phone, $message, $contentVariables),
            default => $this->sendViaLog($phone, $message),
        };
    }

    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("WhatsApp would be sent to {$phone}: {$message}");

        return true;
    }

    protected function sendViaTwilio(string $phone, string $message, ?array $contentVariables = null): bool
    {
        $sid = config('services.whatsapp.twilio_sid');
        $token = config('services.whatsapp.twilio_token');
        $from = config('services.whatsapp.twilio_from');

        if (! $sid || ! $token || ! $from) {
            Log::warning('Twilio WhatsApp credentials are not configured.');

            return false;
        }

        $payload = [
            'From' => $this->normalizeWhatsAppAddress($from),
            'To' => $this->normalizeWhatsAppAddress($phone),
        ];

        $contentSid = config('services.whatsapp.twilio_content_sid');
        $useTemplate = (bool) config('services.whatsapp.use_template', false);

        if ($useTemplate && $contentSid) {
            $variables = $contentVariables ?? $this->defaultContentVariables($message);
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
     * @return array<string, string>
     */
    protected function defaultContentVariables(string $message): array
    {
        $configured = config('services.whatsapp.twilio_content_variables');
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
