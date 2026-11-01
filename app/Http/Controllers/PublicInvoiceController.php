<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use App\Services\Payments\StripeService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PublicInvoiceController extends Controller
{
    public function show(string $token): Response
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        app(TenantManager::class)->set($invoice->company_id);

        $invoice->load(['client', 'items', 'company']);

        return Inertia::render('public/invoice', [
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf(string $token, InvoicePdfService $pdfService): HttpResponse
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        app(TenantManager::class)->set($invoice->company_id);

        return $pdfService->download($invoice);
    }

    public function checkout(string $token, StripeService $stripeService): SymfonyResponse
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        app(TenantManager::class)->set($invoice->company_id);

        if (! in_array($invoice->status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE], true) || $invoice->balance() <= 0) {
            abort(400, 'This invoice is not eligible for online payment.');
        }

        $sessionUrl = $stripeService->createCheckoutSession(
            invoice: $invoice,
            successUrl: route('invoices.public.success', ['token' => $invoice->public_token]),
            cancelUrl: route('invoices.public', ['token' => $invoice->public_token]),
        );

        return Inertia::location($sessionUrl);
    }

    public function paymentSuccess(string $token): Response
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        app(TenantManager::class)->set($invoice->company_id);

        $invoice->load(['client', 'items', 'company']);

        session()->flash('success', 'Payment submitted! The status will update as soon as confirmation is received from the payment gateway.');

        return Inertia::render('public/invoice', [
            'invoice' => $invoice,
        ]);
    }
}
