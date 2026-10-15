<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class PaynowGatewayClient
{
    /**
     * @return array{
     *     status: string,
     *     browserurl?: string,
     *     pollurl?: string,
     *     instructions?: string,
     *     hash?: string,
     *     error?: string
     * }
     */
    public function initiateWeb(
        string $integrationId,
        string $integrationKey,
        string $reference,
        float $amount,
        string $returnUrl,
        string $resultUrl,
        ?string $additionalInfo = null,
        ?string $authEmail = null,
    ): array {
        $fields = [
            'id' => $integrationId,
            'reference' => $reference,
            'amount' => number_format($amount, 2, '.', ''),
            'returnurl' => $returnUrl,
            'resulturl' => $resultUrl,
            'status' => 'Message',
        ];

        if ($additionalInfo !== null && $additionalInfo !== '') {
            $fields['additionalinfo'] = $additionalInfo;
        }

        if ($authEmail !== null && $authEmail !== '') {
            $fields['authemail'] = $authEmail;
        }

        return $this->post(config('services.paynow.initiate_url'), $fields, $integrationKey);
    }

    /**
     * @return array{
     *     status: string,
     *     browserurl?: string,
     *     pollurl?: string,
     *     instructions?: string,
     *     hash?: string,
     *     error?: string
     * }
     */
    public function initiateMobile(
        string $integrationId,
        string $integrationKey,
        string $reference,
        float $amount,
        string $returnUrl,
        string $resultUrl,
        string $phone,
        string $method,
        ?string $additionalInfo = null,
        ?string $authEmail = null,
    ): array {
        $method = strtolower($method);
        if (! in_array($method, ['ecocash', 'onemoney'], true)) {
            throw new InvalidArgumentException('Paynow mobile method must be ecocash or onemoney.');
        }

        $fields = [
            'id' => $integrationId,
            'reference' => $reference,
            'amount' => number_format($amount, 2, '.', ''),
            'returnurl' => $returnUrl,
            'resulturl' => $resultUrl,
            'status' => 'Message',
            'method' => $method,
            'phone' => $phone,
        ];

        if ($additionalInfo !== null && $additionalInfo !== '') {
            $fields['additionalinfo'] = $additionalInfo;
        }

        if ($authEmail !== null && $authEmail !== '') {
            $fields['authemail'] = $authEmail;
        }

        return $this->post(config('services.paynow.remote_url'), $fields, $integrationKey);
    }

    /**
     * @return array{status: string, paid: bool, paynowreference?: string, reference?: string, amount?: string}
     */
    public function poll(string $pollUrl, string $integrationKey): array
    {
        $response = Http::timeout((int) config('services.paynow.timeout', 30))
            ->accept('application/x-www-form-urlencoded')
            ->get($pollUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Paynow poll request failed with HTTP '.$response->status());
        }

        $parsed = $this->parseBody($response->body());

        if (! $this->verifyHash($parsed, $integrationKey)) {
            throw new RuntimeException('Paynow poll response hash mismatch.');
        }

        $status = (string) ($parsed['status'] ?? '');

        return [
            'status' => $status,
            'paid' => $this->isPaidStatus($status),
            'paynowreference' => $parsed['paynowreference'] ?? null,
            'reference' => $parsed['reference'] ?? null,
            'amount' => $parsed['amount'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyHash(array $payload, string $integrationKey): bool
    {
        $provided = $payload['hash'] ?? null;
        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($this->hash($payload, $integrationKey), strtoupper($provided));
    }

    public function isPaidStatus(string $status): bool
    {
        return in_array(strtolower($status), ['paid', 'awaiting delivery', 'delivered'], true);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array{
     *     status: string,
     *     browserurl?: string,
     *     pollurl?: string,
     *     instructions?: string,
     *     hash?: string,
     *     error?: string
     * }
     */
    protected function post(string $url, array $fields, string $integrationKey): array
    {
        $fields['hash'] = $this->hash($fields, $integrationKey);

        $response = Http::timeout((int) config('services.paynow.timeout', 30))
            ->asForm()
            ->post($url, $fields);

        if (! $response->successful()) {
            throw new RuntimeException('Paynow initiate request failed with HTTP '.$response->status());
        }

        $parsed = $this->parseBody($response->body());

        if (! $this->verifyHash($parsed, $integrationKey)) {
            throw new RuntimeException('Paynow initiate response hash mismatch.');
        }

        if (strtolower((string) ($parsed['status'] ?? '')) === 'error') {
            throw new RuntimeException((string) ($parsed['error'] ?? 'Paynow rejected the transaction.'));
        }

        return $parsed;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    protected function hash(array $fields, string $integrationKey): string
    {
        $string = '';

        foreach ($fields as $key => $value) {
            if (strtoupper((string) $key) === 'HASH') {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $string .= (string) $value;
        }

        $string .= $integrationKey;

        return strtoupper(hash('sha512', $string));
    }

    /**
     * @return array<string, string>
     */
    protected function parseBody(string $body): array
    {
        $parsed = [];
        parse_str($body, $parsed);

        $normalized = [];
        foreach ($parsed as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $normalized[strtolower((string) $key)] = (string) $value;
            }
        }

        return $normalized;
    }
}
