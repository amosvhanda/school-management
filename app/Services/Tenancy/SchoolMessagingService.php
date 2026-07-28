<?php

namespace App\Services\Tenancy;

use App\Models\School;
use App\Services\SchoolSettingsService;

class SchoolMessagingService
{
    public function __construct(private SchoolSettingsService $settings) {}

    /**
     * Resolve SMS provider settings for a school, falling back to platform env config.
     *
     * @return array{
     *   enabled: bool,
     *   provider: string,
     *   twilio_sid: ?string,
     *   twilio_token: ?string,
     *   twilio_from: ?string,
     *   africastalking_username: ?string,
     *   africastalking_api_key: ?string,
     *   africastalking_from: ?string,
     *   source: string
     * }
     */
    public function smsConfig(?School $school): array
    {
        $platform = [
            'enabled' => (bool) config('services.sms.enabled', false),
            'provider' => (string) config('services.sms.provider', 'log'),
            'twilio_sid' => config('services.sms.twilio_sid') ?: config('services.whatsapp.twilio_sid'),
            'twilio_token' => config('services.sms.twilio_token') ?: config('services.whatsapp.twilio_token'),
            'twilio_from' => config('services.sms.twilio_from'),
            'africastalking_username' => config('services.sms.africastalking_username'),
            'africastalking_api_key' => config('services.sms.africastalking_api_key'),
            'africastalking_from' => config('services.sms.africastalking_from'),
            'source' => 'platform',
        ];

        if (! $school) {
            return $platform;
        }

        $noticesEnabled = (bool) $this->settings->get($school, 'notifications', 'sms_notices', false);
        $schoolEnabled = (bool) $this->settings->get($school, 'sms', 'enabled', false);

        if (! $schoolEnabled) {
            return array_merge($platform, [
                'enabled' => $platform['enabled'] && $noticesEnabled,
                'source' => $platform['enabled'] ? 'platform' : 'disabled',
            ]);
        }

        $provider = (string) ($this->settings->get($school, 'sms', 'provider') ?: 'log');

        return [
            'enabled' => $noticesEnabled,
            'provider' => $provider,
            'twilio_sid' => $this->settings->get($school, 'sms', 'twilio_sid') ?: $platform['twilio_sid'],
            'twilio_token' => $this->nullableSecret($school, 'sms', 'twilio_token') ?: $platform['twilio_token'],
            'twilio_from' => $this->settings->get($school, 'sms', 'twilio_from') ?: $platform['twilio_from'],
            'africastalking_username' => $this->settings->get($school, 'sms', 'africastalking_username')
                ?: $platform['africastalking_username'],
            'africastalking_api_key' => $this->nullableSecret($school, 'sms', 'africastalking_api_key')
                ?: $platform['africastalking_api_key'],
            'africastalking_from' => $this->settings->get($school, 'sms', 'africastalking_from')
                ?: $platform['africastalking_from'],
            'source' => 'school',
        ];
    }

    /**
     * Resolve WhatsApp provider settings for a school, falling back to platform env config.
     *
     * @return array{
     *   enabled: bool,
     *   provider: string,
     *   twilio_sid: ?string,
     *   twilio_token: ?string,
     *   twilio_from: ?string,
     *   twilio_content_sid: ?string,
     *   twilio_content_variables: ?string,
     *   use_template: bool,
     *   source: string
     * }
     */
    public function whatsappConfig(?School $school): array
    {
        $platform = [
            'enabled' => (bool) config('services.whatsapp.enabled', false),
            'provider' => (string) config('services.whatsapp.provider', 'log'),
            'twilio_sid' => config('services.whatsapp.twilio_sid'),
            'twilio_token' => config('services.whatsapp.twilio_token'),
            'twilio_from' => config('services.whatsapp.twilio_from'),
            'twilio_content_sid' => config('services.whatsapp.twilio_content_sid'),
            'twilio_content_variables' => config('services.whatsapp.twilio_content_variables'),
            'use_template' => (bool) config('services.whatsapp.use_template', false),
            'source' => 'platform',
        ];

        if (! $school) {
            return $platform;
        }

        $noticesEnabled = (bool) $this->settings->get($school, 'notifications', 'whatsapp_notices', false);
        $schoolEnabled = (bool) $this->settings->get($school, 'whatsapp', 'enabled', false);

        if (! $schoolEnabled) {
            return array_merge($platform, [
                'enabled' => $platform['enabled'] && $noticesEnabled,
                'source' => $platform['enabled'] ? 'platform' : 'disabled',
            ]);
        }

        $provider = (string) ($this->settings->get($school, 'whatsapp', 'provider') ?: 'log');

        return [
            'enabled' => $noticesEnabled,
            'provider' => $provider,
            'twilio_sid' => $this->settings->get($school, 'whatsapp', 'twilio_sid') ?: $platform['twilio_sid'],
            'twilio_token' => $this->nullableSecret($school, 'whatsapp', 'twilio_token') ?: $platform['twilio_token'],
            'twilio_from' => $this->settings->get($school, 'whatsapp', 'twilio_from') ?: $platform['twilio_from'],
            'twilio_content_sid' => $this->settings->get($school, 'whatsapp', 'twilio_content_sid')
                ?: $platform['twilio_content_sid'],
            'twilio_content_variables' => $this->settings->get($school, 'whatsapp', 'twilio_content_variables')
                ?: $platform['twilio_content_variables'],
            'use_template' => (bool) $this->settings->get(
                $school,
                'whatsapp',
                'use_template',
                $platform['use_template'],
            ),
            'source' => 'school',
        ];
    }

    protected function nullableSecret(School $school, string $group, string $key): ?string
    {
        $value = $this->settings->get($school, $group, $key);
        if (! is_string($value) || $value === '' || $value === '********') {
            return null;
        }

        return $value;
    }
}
