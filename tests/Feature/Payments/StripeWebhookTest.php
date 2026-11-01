<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Jobs\ProcessStripeWebhook;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\Billing\PaymentService;
use App\Services\Payments\StripeService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Queue;
use Stripe\Exception\SignatureVerificationException;

test('invalid webhook signature returns 400 bad request', function () {
    $mockStripe = Mockery::mock(StripeService::class);
    $mockStripe->shouldReceive('verifyWebhook')
        ->andThrow(new SignatureVerificationException('Invalid signature'));
    $this->app->instance(StripeService::class, $mockStripe);

    $response = $this->postJson('/webhooks/stripe', ['id' => 'evt_test_123'], [
        'Stripe-Signature' => 'invalid_signature_header',
    ]);

    $response->assertStatus(400);
    expect(WebhookEvent::count())->toBe(0);
});

test('valid checkout session completed webhook stores event and queues processing', function () {
    Queue::fake();

    $eventPayload = [
        'id' => 'evt_checkout_12345',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_session_123',
                'amount_total' => 50000,
                'metadata' => [
                    'invoice_id' => '1',
                    'company_id' => '1',
                ],
            ],
        ],
    ];

    $response = $this->postJson('/webhooks/stripe', $eventPayload);

    $response->assertOk();
    $response->assertJson([
        'status' => 'received',
        'event_id' => 'evt_checkout_12345',
    ]);

    $webhookEvent = WebhookEvent::where('provider_event_id', 'evt_checkout_12345')->first();
    expect($webhookEvent)->not->toBeNull()
        ->and($webhookEvent->event_type)->toBe('checkout.session.completed')
        ->and($webhookEvent->status)->toBe('pending');

    Queue::assertPushed(ProcessStripeWebhook::class, function (ProcessStripeWebhook $job) use ($webhookEvent) {
        return $job->webhookEvent->id === $webhookEvent->id;
    });
});

test('duplicate webhook event is ignored and does not double process', function () {
    Queue::fake();

    $eventPayload = [
        'id' => 'evt_duplicate_test_999',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_session_999',
                'amount_total' => 30000,
                'metadata' => ['invoice_id' => '1', 'company_id' => '1'],
            ],
        ],
    ];

    // First delivery
    $firstResponse = $this->postJson('/webhooks/stripe', $eventPayload);
    $firstResponse->assertOk();
    $firstResponse->assertJson(['status' => 'received']);

    // Second delivery (replay / retry)
    $secondResponse = $this->postJson('/webhooks/stripe', $eventPayload);
    $secondResponse->assertOk();
    $secondResponse->assertJson(['status' => 'duplicate_ignored']);

    expect(WebhookEvent::where('provider_event_id', 'evt_duplicate_test_999')->count())->toBe(1);
    Queue::assertPushed(ProcessStripeWebhook::class, 1);
});

test('ProcessStripeWebhook job applies payment and updates invoice status to paid', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 45000,
        'amount_paid' => 0,
    ]);

    $event = WebhookEvent::create([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_process_success_001',
        'event_type' => 'checkout.session.completed',
        'payload' => [
            'id' => 'evt_process_success_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_live_session_abc',
                    'payment_intent' => 'pi_stripe_payment_intent_999',
                    'amount_total' => 45000,
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'company_id' => (string) $company->id,
                    ],
                ],
            ],
        ],
        'status' => 'pending',
    ]);

    $job = new ProcessStripeWebhook($event);
    $job->handle(app(PaymentService::class), app(TenantManager::class));

    $event->refresh();
    expect($event->status)->toBe('processed')
        ->and($event->processed_at)->not->toBeNull()
        ->and($event->error)->toBeNull();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->amount_paid)->toBe(45000)
        ->and($invoice->paid_at)->not->toBeNull();

    $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->amount)->toBe(45000)
        ->and($payment->method)->toBe(PaymentMethod::STRIPE)
        ->and($payment->reference)->toBe('pi_stripe_payment_intent_999');
});

test('ProcessStripeWebhook safely marks failed when metadata references non-existent invoice', function () {
    $event = WebhookEvent::create([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_missing_invoice_001',
        'event_type' => 'checkout.session.completed',
        'payload' => [
            'id' => 'evt_missing_invoice_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_missing',
                    'amount_total' => 10000,
                    'metadata' => [
                        'invoice_id' => '999999',
                        'company_id' => '999999',
                    ],
                ],
            ],
        ],
        'status' => 'pending',
    ]);

    $job = new ProcessStripeWebhook($event);
    $job->handle(app(PaymentService::class), app(TenantManager::class));

    $event->refresh();
    expect($event->status)->toBe('failed')
        ->and($event->error)->toContain('not found');
});

test('public checkout route generates checkout session URL and redirects', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 25000,
        'amount_paid' => 0,
        'public_token' => 'pub_tok_checkout_test',
    ]);

    // Standard Inertia request
    $response = $this->post('/i/pub_tok_checkout_test/checkout', [], ['X-Inertia' => 'true']);

    $response->assertStatus(409); // Inertia::location returns 409 Conflict with X-Inertia-Location header
    expect($response->headers->get('X-Inertia-Location'))->toContain('checkout.stripe.com');
});
