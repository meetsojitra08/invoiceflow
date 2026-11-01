<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Events\InvoicePaid;
use App\Events\PaymentRecorded;
use App\Exceptions\InvalidPaymentException;
use App\Models\Invoice;
use App\Models\Payment;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Apply a full or partial payment to an invoice safely with row locking.
     */
    public function apply(
        Invoice $invoice,
        int $amount,
        PaymentMethod|string $method,
        ?string $reference = null,
        ?DateTimeInterface $paidAt = null,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use ($invoice, $amount, $method, $reference, $paidAt, $notes): Payment {
            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::withoutGlobalScopes()
                ->where('id', $invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedInvoice->status, [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID, InvoiceStatus::OVERDUE], true)) {
                throw InvalidPaymentException::forStatus($lockedInvoice->status->value);
            }

            if ($amount <= 0) {
                throw InvalidPaymentException::nonPositiveAmount();
            }

            $remainingBalance = $lockedInvoice->balance();
            if ($amount > $remainingBalance) {
                throw InvalidPaymentException::exceedsBalance($amount, $remainingBalance);
            }

            $methodValue = $method instanceof PaymentMethod ? $method->value : $method;
            $paidDate = $paidAt ? $paidAt->format('Y-m-d') : now()->toDateString();

            $payment = Payment::create([
                'company_id' => $lockedInvoice->company_id,
                'invoice_id' => $lockedInvoice->id,
                'amount' => $amount,
                'currency' => $lockedInvoice->currency,
                'method' => $methodValue,
                'reference' => $reference,
                'paid_at' => $paidDate,
                'notes' => $notes,
            ]);

            $newAmountPaid = $lockedInvoice->amount_paid + $amount;
            $isFullyPaid = $newAmountPaid >= $lockedInvoice->total;
            $newStatus = $isFullyPaid ? InvoiceStatus::PAID : InvoiceStatus::PARTIALLY_PAID;

            $lockedInvoice->update([
                'amount_paid' => $newAmountPaid,
                'status' => $newStatus,
                'paid_at' => $isFullyPaid ? now() : $lockedInvoice->paid_at,
            ]);

            PaymentRecorded::dispatch($payment);

            if ($isFullyPaid) {
                InvoicePaid::dispatch($lockedInvoice);
            }

            return $payment;
        });
    }
}
