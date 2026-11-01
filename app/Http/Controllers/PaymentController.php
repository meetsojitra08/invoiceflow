<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InvalidPaymentException;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $method = $request->query('method');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $search = $request->query('search');

        $query = Payment::query()->with(['invoice.client']);

        if (is_string($method) && $method !== '' && $method !== 'all') {
            $query->where('method', $method);
        }

        if (is_string($fromDate) && $fromDate !== '') {
            $query->whereDate('paid_at', '>=', $fromDate);
        }

        if (is_string($toDate) && $toDate !== '') {
            $query->whereDate('paid_at', '<=', $toDate);
        }

        if (is_string($search) && $search !== '') {
            $needle = "%{$search}%";
            $query->where(function ($q) use ($needle): void {
                $q->where('reference', 'ILIKE', $needle)
                    ->orWhereHas('invoice', function ($iq) use ($needle): void {
                        $iq->where('number', 'ILIKE', $needle)
                            ->orWhereHas('client', function ($cq) use ($needle): void {
                                $cq->where('name', 'ILIKE', $needle);
                            });
                    });
            });
        }

        $totalCollected = (int) (clone $query)->sum('amount');

        $payments = $query->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'totalCollected' => $totalCollected,
            'filters' => [
                'method' => $method ?? 'all',
                'from_date' => $fromDate ?? '',
                'to_date' => $toDate ?? '',
                'search' => $search ?? '',
            ],
        ]);
    }

    public function store(StorePaymentRequest $request, Invoice $invoice, PaymentService $service): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $service->apply(
                $invoice,
                (int) $validated['amount'],
                (string) $validated['method'],
                $validated['reference'] ?? null,
                Carbon::parse($validated['paid_at']),
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Payment recorded successfully.');
        } catch (InvalidPaymentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}
