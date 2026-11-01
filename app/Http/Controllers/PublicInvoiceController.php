<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

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
}
