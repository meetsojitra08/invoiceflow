<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Exceptions\InvalidPaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Services\Billing\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * List all payments recorded for a specific invoice.
     */
    public function index(Invoice $invoice): AnonymousResourceCollection
    {
        $this->authorize('view', $invoice);

        $payments = $invoice->payments()->orderByDesc('paid_at')->get();

        return PaymentResource::collection($payments);
    }

    /**
     * Record a manual payment against an invoice.
     */
    public function store(StorePaymentRequest $request, Invoice $invoice, PaymentService $paymentService): JsonResponse
    {
        $this->authorize('view', $invoice);

        $method = PaymentMethod::from($request->input('method'));
        $paidAt = Carbon::parse($request->input('paid_at'));

        try {
            $payment = $paymentService->apply(
                invoice: $invoice,
                amount: (int) $request->input('amount'),
                method: $method,
                reference: $request->input('reference'),
                paidAt: $paidAt,
                notes: $request->input('notes'),
            );
        } catch (InvalidPaymentException $e) {
            throw ValidationException::withMessages([
                'amount' => [$e->getMessage()],
            ]);
        }

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
