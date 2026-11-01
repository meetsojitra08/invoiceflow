<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceSent;
use App\Exceptions\InvalidInvoiceTransition;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        protected InvoiceCalculator $calculator,
        protected InvoiceNumberGenerator $numberGenerator,
        protected TenantManager $tenantManager,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(array $data, ?int $companyId = null): Invoice
    {
        $tenantId = $companyId ?? $this->tenantManager->idOrFail();

        return DB::transaction(function () use ($data, $tenantId): Invoice {
            $calculation = $this->calculator->calculate($data['items'] ?? []);

            $number = $this->numberGenerator->generate($tenantId);

            /** @var Company $company */
            $company = Company::findOrFail($tenantId);

            $invoice = Invoice::create([
                'company_id' => $tenantId,
                'client_id' => $data['client_id'],
                'number' => $number,
                'status' => InvoiceStatus::DRAFT,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'currency' => $data['currency'] ?? $company->currency,
                'subtotal' => $calculation['subtotal'],
                'discount_total' => $calculation['discount_total'],
                'tax_total' => $calculation['tax_total'],
                'total' => $calculation['total'],
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
                'public_token' => Str::random(40),
            ]);

            foreach ($calculation['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'discount' => $item['discount'],
                    'line_total' => $item['line_total'],
                    'position' => $item['position'],
                ]);
            }

            return $invoice->load(['items', 'client']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Invoice $invoice, array $data): Invoice
    {
        if (! $invoice->isDraft()) {
            throw InvalidInvoiceTransition::make(
                $invoice->status,
                InvoiceStatus::DRAFT,
                'Only draft invoices can be edited.'
            );
        }

        return DB::transaction(function () use ($invoice, $data): Invoice {
            $calculation = $this->calculator->calculate($data['items'] ?? []);

            $invoice->update([
                'client_id' => $data['client_id'] ?? $invoice->client_id,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $invoice->notes,
                'currency' => $data['currency'] ?? $invoice->currency,
                'subtotal' => $calculation['subtotal'],
                'discount_total' => $calculation['discount_total'],
                'tax_total' => $calculation['tax_total'],
                'total' => $calculation['total'],
            ]);

            $invoice->items()->delete();

            foreach ($calculation['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'discount' => $item['discount'],
                    'line_total' => $item['line_total'],
                    'position' => $item['position'],
                ]);
            }

            return $invoice->fresh(['items', 'client']);
        });
    }

    public function send(Invoice $invoice): Invoice
    {
        if (! $invoice->status->canTransitionTo(InvoiceStatus::SENT, $invoice->amount_paid)) {
            throw InvalidInvoiceTransition::make($invoice->status, InvoiceStatus::SENT);
        }

        $invoice->update([
            'status' => InvoiceStatus::SENT,
            'sent_at' => now(),
        ]);

        InvoiceSent::dispatch($invoice);

        return $invoice;
    }

    public function void(Invoice $invoice, string $reason): Invoice
    {
        if ($invoice->amount_paid > 0) {
            throw InvalidInvoiceTransition::make(
                $invoice->status,
                InvoiceStatus::VOID,
                'Cannot void an invoice with recorded payments.'
            );
        }

        if (! $invoice->status->canTransitionTo(InvoiceStatus::VOID, $invoice->amount_paid)) {
            throw InvalidInvoiceTransition::make($invoice->status, InvoiceStatus::VOID);
        }

        $invoice->update([
            'status' => InvoiceStatus::VOID,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);

        return $invoice;
    }

    public function duplicate(Invoice $invoice): Invoice
    {
        $invoice->loadMissing(['items']);

        $issueDate = Carbon::today()->toDateString();
        $dueDays = Carbon::parse($invoice->issue_date)->diffInDays(Carbon::parse($invoice->due_date), false);
        $dueDate = Carbon::today()->addDays(max(0, $dueDays))->toDateString();

        $items = $invoice->items->map(fn (InvoiceItem $item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'tax_rate' => $item->tax_rate,
            'discount' => $item->discount,
            'position' => $item->position,
        ])->all();

        return $this->createDraft([
            'client_id' => $invoice->client_id,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'items' => $items,
        ], $invoice->company_id);
    }
}
