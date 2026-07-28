<?php

namespace App\Jobs;

use App\Jobs\Concerns\BelongsToTenant;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchEnterpriseWebhookJob implements ShouldQueue
{
    use BelongsToTenant;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600, 7200];

    public function __construct(public int $deliveryId)
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::query()->with('subscription')->find($this->deliveryId);
        if (! $delivery || ! $delivery->subscription) {
            return;
        }

        $this->schoolId = (int) $delivery->school_id;

        if ($delivery->status === WebhookDelivery::STATUS_SUCCESS) {
            return;
        }

        $subscription = $delivery->subscription;
        if (! $subscription->is_active) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_FAILED,
                'last_error' => 'Subscription inactive',
            ]);

            return;
        }

        $secret = $subscription->signingSecret();
        if ($secret === null) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_FAILED,
                'last_error' => 'Missing signing secret',
            ]);

            return;
        }

        $body = [
            'id' => $delivery->id,
            'event_type' => $delivery->event_type,
            'school_id' => $delivery->school_id,
            'payload' => $delivery->payload,
            'attempt' => $delivery->attempts + 1,
            'sent_at' => now()->toIso8601String(),
        ];

        $encoded = json_encode($body, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $encoded, $secret);

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Webhook-Event' => $delivery->event_type,
                    'X-Webhook-Delivery-Id' => (string) $delivery->id,
                    'X-Webhook-Signature' => $signature,
                ])
                ->withBody($encoded, 'application/json')
                ->post($subscription->target_url);
        } catch (Throwable $e) {
            $this->markFailure($delivery, null, null, $e->getMessage());

            return;
        }

        if ($response->successful()) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_SUCCESS,
                'attempts' => $delivery->attempts + 1,
                'response_status' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 4000),
                'last_error' => null,
                'next_retry_at' => null,
                'delivered_at' => now(),
            ]);

            return;
        }

        $this->markFailure(
            $delivery,
            $response->status(),
            mb_substr($response->body(), 0, 4000),
            'HTTP '.$response->status()
        );
    }

    protected function markFailure(
        WebhookDelivery $delivery,
        ?int $responseStatus,
        ?string $responseBody,
        string $error,
    ): void {
        $attempts = $delivery->attempts + 1;
        $maxAttempts = count($this->backoff) + 1;
        $willRetry = $attempts < $maxAttempts;
        $nextRetryAt = $willRetry ? now()->addSeconds($this->backoff[$attempts - 1] ?? 7200) : null;

        $delivery->update([
            'status' => $willRetry ? WebhookDelivery::STATUS_PENDING : WebhookDelivery::STATUS_FAILED,
            'attempts' => $attempts,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'last_error' => $error,
            'next_retry_at' => $nextRetryAt,
        ]);

        if ($willRetry) {
            self::dispatch($delivery->id)->delay($nextRetryAt);
        } else {
            Log::warning('Enterprise webhook delivery failed permanently', [
                'delivery_id' => $delivery->id,
                'event_type' => $delivery->event_type,
                'error' => $error,
            ]);
        }
    }
}
