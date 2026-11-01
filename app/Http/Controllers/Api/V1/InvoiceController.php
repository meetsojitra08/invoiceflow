<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    /**
     * List invoices with filtering and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));

        $query = Invoice::query()
            ->with(['client:id,name,email'])
            ->search($request->input('search'))
            ->status($request->input('status'))
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        if ($request->filled('from')) {
            $query->where('issue_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('issue_date', '<=', $request->input('to'));
        }

        $invoices = $query->paginate($perPage);

        return InvoiceResource::collection($invoices);
    }

    /**
     * Create an invoice draft or create and send immediately.
     */
    public function store(StoreInvoiceRequest $request, InvoiceService $invoiceService): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $invoiceService->createDraft($request->validated());

        if ($request->input('action') === 'send' && $request->user()?->can('send', $invoice)) {
            $invoiceService->send($invoice);
        }

        $invoice->load(['client', 'items', 'payments']);

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show invoice details with eager-loaded relations.
     */
    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'payments']);

        return new InvoiceResource($invoice);
    }

    /**
     * Update an invoice draft.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $invoiceService): InvoiceResource
    {
        $this->authorize('update', $invoice);

        $invoice = $invoiceService->updateDraft($invoice, $request->validated());

        if ($request->input('action') === 'send' && $request->user()?->can('send', $invoice)) {
            $invoiceService->send($invoice);
        }

        $invoice->load(['client', 'items', 'payments']);

        return new InvoiceResource($invoice);
    }

    /**
     * Send an invoice to the client by email.
     */
    public function send(Invoice $invoice, InvoiceService $invoiceService): InvoiceResource
    {
        $this->authorize('send', $invoice);

        $invoiceService->send($invoice);

        $invoice->load(['client', 'items', 'payments']);

        return new InvoiceResource($invoice);
    }

    /**
     * Void an invoice.
     */
    public function void(Request $request, Invoice $invoice, InvoiceService $invoiceService): InvoiceResource
    {
        $this->authorize('void', $invoice);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $invoiceService->void($invoice, $validated['reason']);

        $invoice->load(['client', 'items', 'payments']);

        return new InvoiceResource($invoice);
    }

    /**
     * Download rendered invoice PDF.
     */
    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        $this->authorize('view', $invoice);

        return $pdfService->download($invoice);
    }
}
