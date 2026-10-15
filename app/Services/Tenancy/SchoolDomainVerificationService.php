<?php

namespace App\Services\Tenancy;

use App\Models\SchoolDomain;
use Illuminate\Support\Str;

class SchoolDomainVerificationService
{
    public function generateToken(): string
    {
        return Str::lower(Str::random(32));
    }

    /**
     * @return array{dns_host: string, dns_type: string, dns_value: string}
     */
    public function instructions(SchoolDomain $domain): array
    {
        return [
            'dns_host' => $this->verificationHost($domain->domain),
            'dns_type' => 'TXT',
            'dns_value' => $this->expectedRecordValue($domain),
        ];
    }

    public function verifyDns(SchoolDomain $domain): bool
    {
        if (! function_exists('dns_get_record')) {
            return false;
        }

        $host = $this->verificationHost($domain->domain);
        $expected = $this->expectedRecordValue($domain);
        $records = @dns_get_record($host, DNS_TXT);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? $record['entries'][0] ?? null;
            if (is_string($txt) && trim($txt) === $expected) {
                return true;
            }
        }

        return false;
    }

    public function verificationHost(string $domain): string
    {
        return '_schoolerp-verify.'.strtolower(trim($domain));
    }

    public function expectedRecordValue(SchoolDomain $domain): string
    {
        return 'schoolerp-verify='.(string) $domain->verification_token;
    }
}
