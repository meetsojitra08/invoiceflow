<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Events\InvoicePaid;
use App\Events\PaymentRecorded;
use App\Exceptions\InvalidPaymentException;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Billing\PaymentService;
use Illuminate\Support\Facades\Event;

test('partial payment updates amount paid and transitions status to partially paid', function () {
    Event::fake([PaymentRecorded::class, InvoicePaid::class]);

    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 100000, // ₹1,000.00
        'amount_paid' => 0,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->apply(
        invoice: $invoice,
        amount: 40000, // ₹400.00
        method: PaymentMethod::BANK_TRANSFER,
        reference: 'TXN-12345'
    );

    expect($payment)->not->toBeNull()
        ->and($payment->amount)->toBe(40000)
        ->and($payment->method)->toBe(PaymentMethod::BANK_TRANSFER)
        ->and($payment->reference)->toBe('TXN-12345');

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(40000)
        ->and($invoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID)
        ->and($invoice->paid_at)->toBeNull();

    Event::assertDispatched(PaymentRecorded::class);
    Event::assertNotDispatched(InvoicePaid::class);
});

test('full payment transitions invoice status to paid and dispatches InvoicePaid', function () {
    Event::fake([PaymentRecorded::class, InvoicePaid::class]);

    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'total' => 100000,
        'amount_paid' => 40000,
    ]);

    $service = app(PaymentService::class);
    $payment = $service->apply(
        invoice: $invoice,
        amount: 60000,
        method: PaymentMethod::STRIPE,
        reference: 'ch_123456789'
    );

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(100000)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->paid_at)->not->toBeNull();

    Event::assertDispatched(PaymentRecorded::class);
    Event::assertDispatched(InvoicePaid::class);
});

test('overpayment throws InvalidPaymentException and does not change invoice state', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 50000,
        'amount_paid' => 0,
    ]);

    $service = app(PaymentService::class);

    expect(fn () => $service->apply(
        invoice: $invoice,
        amount: 60000,
        method: PaymentMethod::CASH
    ))->toThrow(InvalidPaymentException::class);

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::SENT);
});

test('payment on draft or void invoice throws InvalidPaymentException', function (InvoiceStatus $invalidStatus) {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => $invalidStatus,
        'total' => 50000,
        'amount_paid' => 0,
    ]);

    $service = app(PaymentService::class);

    expect(fn () => $service->apply(
        invoice: $invoice,
        amount: 20000,
        method: PaymentMethod::CASH
    ))->toThrow(InvalidPaymentException::class);
})->with([
    'draft' => InvoiceStatus::DRAFT,
    'void' => InvoiceStatus::VOID,
    'paid' => InvoiceStatus::PAID,
]);
