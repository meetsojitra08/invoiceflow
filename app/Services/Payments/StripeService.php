<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Invoice;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    public function createCheckoutSession(Invoice $invoice, string $successUrl, string $cancelUrl): string
    {
        $invoice->loadMissing(['client', 'company']);
        $secretKey = config('services.stripe.secret');

        if (! $secretKey) {
            // Fake URL for environments without live Stripe credentials
            return "https://checkout.stripe.com/pay/fake_session_{$invoice->public_token}";
        }

        $stripe = new StripeClient($secretKey);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'product_data' => [
                        'name' => "Invoice {$invoice->number} - {$invoice->company->name}",
                    ],
                    'unit_amount' => $invoice->balance(),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
                'company_id' => (string) $invoice->company_id,
            ],
            'customer_email' => $invoice->client?->email,
        ]);

        return $session->url ?? "https://checkout.stripe.com/pay/session_{$session->id}";
    }

    /**
     * @return array<string, mixed>
     *
     * @throws SignatureVerificationException
     */
    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! $webhookSecret) {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        }

        $event = Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);

        return $event->toArray();
    }
}
