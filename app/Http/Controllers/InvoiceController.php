<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\Billing\InvoiceCalculator;
use App\Services\Billing\InvoicePdfService;
use App\Services\Billing\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function calculate(Request $request, InvoiceCalculator $calculator): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.discount' => ['nullable', 'integer', 'min:0'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.description' => ['nullable', 'string'],
        ]);

        $result = $calculator->calculate($validated['items']);

        return response()->json($result);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $search = $request->query('search');
        $status = $request->query('status');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = Invoice::query()->with('client');

        if (is_string($search) && $search !== '') {
            $query->search($search);
        }

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $query->status($status);
        }

        if (is_string($fromDate) && $fromDate !== '') {
            $query->whereDate('issue_date', '>=', $fromDate);
        }

        if (is_string($toDate) && $toDate !== '') {
            $query->whereDate('issue_date', '<=', $toDate);
        }

        $invoices = $query->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Calculate status counts for quick filter tabs
        $counts = [
            'all' => Invoice::count(),
            'draft' => Invoice::where('status', InvoiceStatus::DRAFT->value)->count(),
            'sent' => Invoice::where('status', InvoiceStatus::SENT->value)->count(),
            'partially_paid' => Invoice::where('status', InvoiceStatus::PARTIALLY_PAID->value)->count(),
            'paid' => Invoice::where('status', InvoiceStatus::PAID->value)->count(),
            'overdue' => Invoice::where('status', InvoiceStatus::OVERDUE->value)->count(),
            'void' => Invoice::where('status', InvoiceStatus::VOID->value)->count(),
        ];

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? 'all',
                'from_date' => $fromDate ?? '',
                'to_date' => $toDate ?? '',
            ],
            'statusCounts' => $counts,
            'can' => [
                'create' => $request->user()?->can('create', Invoice::class) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Invoice::class);

        $clients = Client::orderBy('name')->get(['id', 'name', 'email']);
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit_price', 'tax_rate']);

        return Inertia::render('invoices/create', [
            'clients' => $clients,
            'products' => $products,
            'today' => Carbon::today()->toDateString(),
            'defaultDueDate' => Carbon::today()->addDays(14)->toDateString(),
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $service): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $service->createDraft($request->validated());

        if ($request->input('action') === 'send' && $request->user()?->can('send', $invoice)) {
            $service->send($invoice);

            return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created and sent successfully.');
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice draft saved successfully.');
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items', 'payments', 'reminders']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'publicUrl' => route('invoices.public', ['token' => $invoice->public_token]),
            'can' => [
                'edit' => auth()->user()?->can('update', $invoice) ?? false,
                'send' => auth()->user()?->can('send', $invoice) ?? false,
                'void' => auth()->user()?->can('void', $invoice) ?? false,
                'duplicate' => auth()->user()?->can('duplicate', $invoice) ?? false,
                'delete' => auth()->user()?->can('delete', $invoice) ?? false,
                'recordPayment' => in_array(auth()->user()?->currentRole(), ['owner', 'accountant'], true),
            ],
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->load(['client', 'items']);
        $clients = Client::orderBy('name')->get(['id', 'name', 'email']);
        $products = Product::active()->orderBy('name')->get(['id', 'name', 'description', 'unit_price', 'tax_rate']);

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'clients' => $clients,
            'products' => $products,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $service->updateDraft($invoice, $request->validated());

        if ($request->input('action') === 'send' && $request->user()?->can('send', $invoice)) {
            $service->send($invoice);

            return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated and sent.');
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice draft updated.');
    }

    public function send(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $validated = $request->validate([
            'recipient_email' => ['nullable', 'email'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $recipient = $validated['recipient_email'] ?? $invoice->client?->email;
        if (! $recipient) {
            return back()->withErrors(['recipient_email' => 'Please provide a recipient email address.']);
        }

        $service->send($invoice, $recipient, $validated['message'] ?? null);

        return back()->with('success', 'Invoice sent successfully.');
    }

    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): \Illuminate\Http\Response
    {
        $this->authorize('view', $invoice);

        return $pdfService->download($invoice);
    }

    public function void(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $service->void($invoice, $validated['reason']);

        return back()->with('success', 'Invoice has been voided.');
    }

    public function duplicate(Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $this->authorize('duplicate', $invoice);

        $copy = $service->duplicate($invoice);

        return redirect()->route('invoices.edit', $copy)->with('success', 'Invoice duplicated as draft.');
    }
}
