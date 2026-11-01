<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can record payment and list payments on an invoice via api', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    Sanctum::actingAs($user, ['*']);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 20000,
        'amount_paid' => 0,
    ]);

    // 1. Record partial payment
    $createResponse = $this->postJson(route('api.v1.invoices.payments.store', ['invoice' => $invoice->id]), [
        'amount' => 8000,
        'method' => PaymentMethod::BANK_TRANSFER->value,
        'reference' => 'WIRE-2026-001',
        'paid_at' => '2026-11-01',
        'notes' => 'Partial installment',
    ]);

    $createResponse->assertStatus(201);
    $createResponse->assertJsonPath('data.amount', 8000);
    $createResponse->assertJsonPath('data.method', 'bank_transfer');
    $createResponse->assertJsonPath('data.reference', 'WIRE-2026-001');

    expect($invoice->fresh()->amount_paid)->toBe(8000);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PARTIALLY_PAID);

    // 2. List payments for invoice
    $listResponse = $this->getJson(route('api.v1.invoices.payments.index', ['invoice' => $invoice->id]));
    $listResponse->assertSuccessful();
    $listResponse->assertJsonCount(1, 'data');
    expect($listResponse->json('data.0.reference'))->toBe('WIRE-2026-001');
});

test('overpayment via api is rejected with 422', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    Sanctum::actingAs($user, ['*']);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'total' => 10000,
        'amount_paid' => 0,
    ]);

    $response = $this->postJson(route('api.v1.invoices.payments.store', ['invoice' => $invoice->id]), [
        'amount' => 15000, // exceeds balance of 10000!
        'method' => PaymentMethod::CASH->value,
        'paid_at' => '2026-11-01',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('exceeds the remaining balance');
});

test('foreign invoice payment is rejected with 404 for different tenant', function () {
    $companyA = Company::factory()->create();
    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $userA->companies()->attach($companyA->id, ['role' => 'owner']);

    $companyB = Company::factory()->create();
    $clientB = Client::factory()->create(['company_id' => $companyB->id]);
    $invoiceB = Invoice::factory()->create([
        'company_id' => $companyB->id,
        'client_id' => $clientB->id,
        'status' => InvoiceStatus::SENT,
        'total' => 10000,
    ]);

    Sanctum::actingAs($userA, ['*']);

    $this->postJson(route('api.v1.invoices.payments.store', ['invoice' => $invoiceB->id]), [
        'amount' => 5000,
        'method' => PaymentMethod::CASH->value,
        'paid_at' => '2026-11-01',
    ])->assertStatus(404);
});
