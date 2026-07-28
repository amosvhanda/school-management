<?php

namespace App\Services\Tenancy;

use App\Models\School;
use App\Services\SchoolSettingsService;
use Illuminate\Support\Facades\Config;

class SchoolMailService
{
    public function __construct(private SchoolSettingsService $settings) {}

    /**
     * Apply tenant-specific from-address / SMTP config for the current request or job.
     *
     * @return array{mailer: string, from_address: string, from_name: string}
     */
    public function applyForSchool(?School $school): array
    {
        $defaults = [
            'mailer' => (string) config('mail.default', 'log'),
            'from_address' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
        ];

        if (! $school) {
            return $defaults;
        }

        $fromAddress = $this->settings->get($school, 'mail', 'from_address')
            ?: $school->email
            ?: $defaults['from_address'];
        $fromName = $this->settings->get($school, 'mail', 'from_name')
            ?: $this->settings->get($school, 'branding', 'school_name')
            ?: $school->name
            ?: $defaults['from_name'];

        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        $enabled = (bool) $this->settings->get($school, 'mail', 'enabled', false);
        $host = $this->settings->get($school, 'mail', 'smtp_host');

        if (! $enabled || ! is_string($host) || trim($host) === '') {
            return [
                'mailer' => $defaults['mailer'],
                'from_address' => (string) $fromAddress,
                'from_name' => (string) $fromName,
            ];
        }

        $port = (int) ($this->settings->get($school, 'mail', 'smtp_port') ?: 587);
        $encryption = $this->settings->get($school, 'mail', 'smtp_encryption') ?: 'tls';
        $scheme = in_array($encryption, ['ssl', 'tls'], true) ? $encryption : null;

        Config::set('mail.mailers.school_smtp', [
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'username' => $this->settings->get($school, 'mail', 'smtp_username'),
            'password' => $this->settings->get($school, 'mail', 'smtp_password'),
            'timeout' => null,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);
        Config::set('mail.default', 'school_smtp');

        return [
            'mailer' => 'school_smtp',
            'from_address' => (string) $fromAddress,
            'from_name' => (string) $fromName,
        ];
    }
}
