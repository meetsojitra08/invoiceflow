<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can create, view, update, send, void, and download invoice pdf via api', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    Sanctum::actingAs($user, ['*']);

    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Apex Corp']);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'unit_price' => 100000,
        'tax_rate' => 18,
    ]);

    // 1. Create invoice draft
    $createResponse = $this->postJson(route('api.v1.invoices.store'), [
        'client_id' => $client->id,
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-15',
        'notes' => 'API Generated invoice',
        'items' => [
            [
                'product_id' => $product->id,
                'description' => 'Development Services',
                'quantity' => 2,
                'unit_price' => 100000,
                'tax_rate' => 18,
                'discount' => 10000, // 100 discount
            ],
        ],
    ]);

    $createResponse->assertStatus(201);
    $invoiceId = $createResponse->json('data.id');
    expect($createResponse->json('data.status'))->toBe(InvoiceStatus::DRAFT->value);
    expect($createResponse->json('data.subtotal'))->toBe(200000);
    expect($createResponse->json('data.discount_total'))->toBe(10000);
    expect($createResponse->json('data.tax_total'))->toBe(34200); // 18% on (200000 - 10000) = 34200
    expect($createResponse->json('data.total'))->toBe(224200);
    expect($createResponse->json('data.public_token'))->toBeNull(); // ensure internal token is never exposed

    // 2. Show invoice
    $showResponse = $this->getJson(route('api.v1.invoices.show', ['invoice' => $invoiceId]));
    $showResponse->assertSuccessful();
    $showResponse->assertJsonPath('data.client.name', 'Apex Corp');
    $showResponse->assertJsonCount(1, 'data.items');

    // 3. Update invoice draft
    $updateResponse = $this->putJson(route('api.v1.invoices.update', ['invoice' => $invoiceId]), [
        'client_id' => $client->id,
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-20',
        'items' => [
            [
                'description' => 'Single Consulting Hour',
                'quantity' => 1,
                'unit_price' => 50000,
                'tax_rate' => 0,
                'discount' => 0,
            ],
        ],
    ]);
    $updateResponse->assertSuccessful();
    expect($updateResponse->json('data.total'))->toBe(50000);

    // 4. Send invoice
    $sendResponse = $this->postJson(route('api.v1.invoices.send', ['invoice' => $invoiceId]));
    $sendResponse->assertSuccessful();
    expect($sendResponse->json('data.status'))->toBe(InvoiceStatus::SENT->value);

    // 5. Download PDF
    $pdfResponse = $this->get(route('api.v1.invoices.pdf', ['invoice' => $invoiceId]));
    $pdfResponse->assertSuccessful();
    $pdfResponse->assertHeader('content-type', 'application/pdf');

    // 6. Void invoice
    $voidResponse = $this->postJson(route('api.v1.invoices.void', ['invoice' => $invoiceId]), [
        'reason' => 'Duplicate booking',
    ]);
    $voidResponse->assertSuccessful();
    expect($voidResponse->json('data.status'))->toBe(InvoiceStatus::VOID->value);
    expect($voidResponse->json('data.void_reason'))->toBe('Duplicate booking');
});

test('foreign client or product id in invoice payload is rejected with 422', function () {
    $companyA = Company::factory()->create();
    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $userA->companies()->attach($companyA->id, ['role' => 'owner']);

    $companyB = Company::factory()->create();
    $clientB = Client::factory()->create(['company_id' => $companyB->id]);

    Sanctum::actingAs($userA, ['*']);

    $response = $this->postJson(route('api.v1.invoices.store'), [
        'client_id' => $clientB->id,
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-15',
        'items' => [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['client_id']);
});

test('tenant cannot access another company invoice via api', function () {
    $companyA = Company::factory()->create();
    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $userA->companies()->attach($companyA->id, ['role' => 'owner']);

    $companyB = Company::factory()->create();
    $clientB = Client::factory()->create(['company_id' => $companyB->id]);
    $invoiceB = Invoice::factory()->create([
        'company_id' => $companyB->id,
        'client_id' => $clientB->id,
    ]);

    Sanctum::actingAs($userA, ['*']);

    $this->getJson(route('api.v1.invoices.show', ['invoice' => $invoiceB->id]))
        ->assertStatus(404);

    $this->postJson(route('api.v1.invoices.send', ['invoice' => $invoiceB->id]))
        ->assertStatus(404);
});
