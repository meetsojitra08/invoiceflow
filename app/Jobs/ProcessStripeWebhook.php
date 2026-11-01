<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\WebhookEvent;
use App\Services\Billing\PaymentService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public WebhookEvent $webhookEvent
    ) {}

    public function handle(PaymentService $paymentService, TenantManager $tenantManager): void
    {
        $payload = $this->webhookEvent->payload;
        $eventType = $this->webhookEvent->event_type;

        if ($eventType !== 'checkout.session.completed') {
            $this->webhookEvent->update([
                'status' => 'ignored',
                'processed_at' => now(),
            ]);

            return;
        }

        try {
            $session = $payload['data']['object'] ?? [];
            $metadata = $session['metadata'] ?? [];

            $invoiceId = isset($metadata['invoice_id']) ? (int) $metadata['invoice_id'] : null;
            $companyId = isset($metadata['company_id']) ? (int) $metadata['company_id'] : null;

            if (! $invoiceId || ! $companyId) {
                $this->webhookEvent->update([
                    'status' => 'failed',
                    'error' => 'Missing invoice_id or company_id in metadata',
                    'processed_at' => now(),
                ]);

                return;
            }

            /** @var Invoice|null $invoice */
            $invoice = Invoice::withoutGlobalScopes()
                ->where('id', $invoiceId)
                ->where('company_id', $companyId)
                ->first();

            if (! $invoice) {
                $this->webhookEvent->update([
                    'status' => 'failed',
                    'error' => "Invoice #{$invoiceId} not found for company #{$companyId}",
                    'processed_at' => now(),
                ]);

                return;
            }

            $amountTotal = (int) ($session['amount_total'] ?? 0);
            $paymentReference = (string) ($session['payment_intent'] ?? $session['id'] ?? 'stripe_online');

            $tenantManager->execute($companyId, function () use ($paymentService, $invoice, $amountTotal, $paymentReference, $session): void {
                $paymentService->apply(
                    invoice: $invoice,
                    amount: $amountTotal,
                    method: PaymentMethod::STRIPE,
                    reference: $paymentReference,
                    paidAt: now(),
                    notes: "Stripe Checkout session {$session['id']}"
                );
            });

            $this->webhookEvent->update([
                'status' => 'processed',
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('Stripe webhook processing failed: '.$e->getMessage(), [
                'webhook_event_id' => $this->webhookEvent->id,
                'exception' => $e,
            ]);

            $this->webhookEvent->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
