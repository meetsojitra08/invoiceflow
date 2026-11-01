<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhook;
use App\Models\WebhookEvent;
use App\Services\Payments\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripeService): Response|JsonResponse
    {
        $payload = (string) $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $stripeService->verifyWebhook($payload, $sigHeader);
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        } catch (Throwable) {
            return response('Bad request', 400);
        }

        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');

        if (! $eventId || ! $eventType) {
            return response('Malformed event data', 400);
        }

        // Idempotency: Duplicate webhooks must never double-count
        $existing = WebhookEvent::where('provider', 'stripe')
            ->where('provider_event_id', $eventId)
            ->first();

        if ($existing !== null) {
            return response()->json([
                'status' => 'duplicate_ignored',
                'event_id' => $eventId,
            ], 200);
        }

        $webhookEvent = WebhookEvent::create([
            'provider' => 'stripe',
            'provider_event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $event,
            'status' => 'pending',
        ]);

        ProcessStripeWebhook::dispatch($webhookEvent);

        return response()->json([
            'status' => 'received',
            'event_id' => $eventId,
        ], 200);
    }
}
