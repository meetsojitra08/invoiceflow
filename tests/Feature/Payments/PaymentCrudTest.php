<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

test('owner and accountant can view payments list scoped to tenant', function (Role $role) {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $companyA->users()->attach($userA->id, ['role' => $role->value]);

    $clientA = Client::factory()->create(['company_id' => $companyA->id]);
    $clientB = Client::factory()->create(['company_id' => $companyB->id]);

    $invoiceA = Invoice::factory()->create(['company_id' => $companyA->id, 'client_id' => $clientA->id]);
    $invoiceB = Invoice::factory()->create(['company_id' => $companyB->id, 'client_id' => $clientB->id]);

    Payment::factory()->create([
        'company_id' => $companyA->id,
        'invoice_id' => $invoiceA->id,
        'reference' => 'PAY-TENANT-A',
    ]);
    Payment::factory()->create([
        'company_id' => $companyB->id,
        'invoice_id' => $invoiceB->id,
        'reference' => 'PAY-TENANT-B',
    ]);

    $response = actingAsTenant($userA, $companyA)->get('/payments');

    $response->assertOk();
    $response->assertSee('PAY-TENANT-A');
    $response->assertDontSee('PAY-TENANT-B');
})->with([
    'owner' => Role::OWNER,
    'accountant' => Role::ACCOUNTANT,
]);

test('staff user cannot view payments list', function () {
    $company = Company::factory()->create();
    $staff = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($staff->id, ['role' => Role::STAFF->value]);

    $response = actingAsTenant($staff, $company)->get('/payments');

    $response->assertForbidden();
});

test('owner can record payment on sent invoice', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 50000,
        'amount_paid' => 0,
    ]);

    $response = actingAsTenant($owner, $company)->post("/invoices/{$invoice->id}/payments", [
        'amount' => 25000,
        'method' => PaymentMethod::BANK_TRANSFER->value,
        'reference' => 'WIRE-999',
        'paid_at' => now()->toDateString(),
        'notes' => 'Received via wire transfer.',
    ]);

    $response->assertRedirect();

    $payment = Payment::where('invoice_id', $invoice->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->amount)->toBe(25000)
        ->and($payment->method)->toBe(PaymentMethod::BANK_TRANSFER)
        ->and($payment->reference)->toBe('WIRE-999');

    expect($invoice->fresh()->amount_paid)->toBe(25000)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::PARTIALLY_PAID);
});

test('staff user cannot record payment', function () {
    $company = Company::factory()->create();
    $staff = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($staff->id, ['role' => Role::STAFF->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
    ]);

    $response = actingAsTenant($staff, $company)->post("/invoices/{$invoice->id}/payments", [
        'amount' => 10000,
        'method' => PaymentMethod::CASH->value,
        'paid_at' => now()->toDateString(),
    ]);

    $response->assertForbidden();
    expect(Payment::count())->toBe(0);
});

test('overpayment via http returns validation error and does not create payment', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 20000,
        'amount_paid' => 0,
    ]);

    $response = actingAsTenant($owner, $company)->post("/invoices/{$invoice->id}/payments", [
        'amount' => 30000, // exceeds 20000 balance!
        'method' => PaymentMethod::CASH->value,
        'paid_at' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('amount');
    expect(Payment::count())->toBe(0);
});
