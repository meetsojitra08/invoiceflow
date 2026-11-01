<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $search = $request->query('search');

        $clients = Client::query()
            ->search(is_string($search) ? $search : null)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search ?? '',
            ],
            'can' => [
                'create' => $request->user()?->can('create', Client::class) ?? false,
                'delete' => $request->user()?->can('delete', new Client) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        Client::create($request->validated());

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        $invoices = $client->invoices()
            ->latest('issue_date')
            ->latest('id')
            ->get();

        $totalBilled = (int) $invoices
            ->whereIn('status', [
                InvoiceStatus::SENT,
                InvoiceStatus::PARTIALLY_PAID,
                InvoiceStatus::PAID,
                InvoiceStatus::OVERDUE,
            ])
            ->sum('total');

        $outstandingBalance = (int) $invoices
            ->whereIn('status', [
                InvoiceStatus::SENT,
                InvoiceStatus::PARTIALLY_PAID,
                InvoiceStatus::OVERDUE,
            ])
            ->sum(fn ($invoice) => max(0, $invoice->total - ($invoice->amount_paid ?? 0)));

        return Inertia::render('clients/show', [
            'client' => $client,
            'stats' => [
                'total_billed' => $totalBilled,
                'outstanding_balance' => $outstandingBalance,
                'invoices_count' => $invoices->count(),
            ],
            'invoices' => $invoices,
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('clients/edit', [
            'client' => $client,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client archived successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        /** @var Client $client */
        $client = Client::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $client);

        $client->restore();

        return redirect()->route('clients.index')->with('success', 'Client restored successfully.');
    }
}
