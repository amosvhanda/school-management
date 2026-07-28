<?php

namespace App\Services\Enterprise;

use App\Jobs\DispatchEnterpriseWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Collection;

class EnterpriseWebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(int $schoolId, string $eventType, array $payload): int
    {
        $subscriptions = $this->matchingSubscriptions($schoolId, $eventType);
        $queued = 0;

        foreach ($subscriptions as $subscription) {
            $delivery = WebhookDelivery::create([
                'school_id' => $schoolId,
                'subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => WebhookDelivery::STATUS_PENDING,
            ]);

            DispatchEnterpriseWebhookJob::dispatch($delivery->id);
            $queued++;
        }

        return $queued;
    }

    public function retryDelivery(WebhookDelivery $delivery): void
    {
        if ($delivery->status === WebhookDelivery::STATUS_SUCCESS) {
            return;
        }

        $delivery->update([
            'status' => WebhookDelivery::STATUS_PENDING,
            'next_retry_at' => null,
            'last_error' => null,
        ]);

        DispatchEnterpriseWebhookJob::dispatch($delivery->id);
    }

    public function retryFailed(?int $schoolId = null, int $limit = 50): int
    {
        $query = WebhookDelivery::query()
            ->where('status', WebhookDelivery::STATUS_FAILED)
            ->orderBy('updated_at');

        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        $retried = 0;
        foreach ($query->limit($limit)->get() as $delivery) {
            $this->retryDelivery($delivery);
            $retried++;
        }

        return $retried;
    }

    /**
     * @return Collection<int, WebhookSubscription>
     */
    protected function matchingSubscriptions(int $schoolId, string $eventType): Collection
    {
        return WebhookSubscription::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($query) use ($eventType) {
                $query->where('event_type', $eventType)
                    ->orWhere('event_type', '*');
            })
            ->get();
    }
}
