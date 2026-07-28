<?php

namespace Tests\Feature;

use App\Jobs\DispatchEnterpriseWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\Enterprise\EnterpriseWebhookDispatcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EnterpriseWebhookDispatcherTest extends TestCase
{
    public function test_store_webhook_subscription_returns_signing_secret_once(): void
    {
        $auth = $this->createAuthenticatedUser();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson('/api/v1/enterprise/integrations/webhooks', [
                'event_type' => 'payment.completed',
                'target_url' => 'https://example.com/hooks/payments',
            ])
            ->assertCreated();

        $this->assertNotEmpty($response->json('secret'));
        $this->assertDatabaseHas('webhook_subscriptions', [
            'school_id' => $auth['school']->id,
            'event_type' => 'payment.completed',
        ]);
    }

    public function test_dispatcher_queues_signed_delivery_job(): void
    {
        Queue::fake();

        $auth = $this->createAuthenticatedUser();
        WebhookSubscription::create([
            'school_id' => $auth['school']->id,
            'event_type' => 'payment.completed',
            'target_url' => 'https://example.com/hooks/payments',
            'secret' => 'signing-secret',
            'is_active' => true,
        ]);

        $queued = app(EnterpriseWebhookDispatcher::class)->dispatch(
            $auth['school']->id,
            'payment.completed',
            ['invoice_id' => 10, 'amount' => 25.5],
        );

        $this->assertSame(1, $queued);
        Queue::assertPushed(DispatchEnterpriseWebhookJob::class);
    }

    public function test_delivery_job_marks_success_on_2xx_response(): void
    {
        Http::fake(['https://example.com/hooks/payments' => Http::response(['ok' => true], 200)]);

        $auth = $this->createAuthenticatedUser();
        $subscription = WebhookSubscription::create([
            'school_id' => $auth['school']->id,
            'event_type' => 'payment.completed',
            'target_url' => 'https://example.com/hooks/payments',
            'secret' => 'signing-secret',
            'is_active' => true,
        ]);

        $delivery = WebhookDelivery::create([
            'school_id' => $auth['school']->id,
            'subscription_id' => $subscription->id,
            'event_type' => 'payment.completed',
            'payload' => ['invoice_id' => 10],
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        (new DispatchEnterpriseWebhookJob($delivery->id))->handle();

        $delivery->refresh();
        $this->assertSame(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
        $this->assertSame(200, $delivery->response_status);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/hooks/payments'
                && $request->hasHeader('X-Webhook-Signature')
                && $request->hasHeader('X-Webhook-Event', 'payment.completed');
        });
    }

    public function test_failed_delivery_can_be_retried_via_api(): void
    {
        Queue::fake();

        $auth = $this->createAuthenticatedUser();
        $subscription = WebhookSubscription::create([
            'school_id' => $auth['school']->id,
            'event_type' => 'student.graduated',
            'target_url' => 'https://example.com/hooks/students',
            'secret' => 'signing-secret',
            'is_active' => true,
        ]);

        $delivery = WebhookDelivery::create([
            'school_id' => $auth['school']->id,
            'subscription_id' => $subscription->id,
            'event_type' => 'student.graduated',
            'payload' => ['student_id' => 5],
            'status' => WebhookDelivery::STATUS_FAILED,
            'attempts' => 3,
            'last_error' => 'HTTP 500',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer '.$auth['token']])
            ->postJson("/api/v1/enterprise/integrations/webhooks/deliveries/{$delivery->id}/retry")
            ->assertOk()
            ->assertJsonPath('data.status', WebhookDelivery::STATUS_PENDING);

        Queue::assertPushed(DispatchEnterpriseWebhookJob::class);
    }
}
