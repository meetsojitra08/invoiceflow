<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenancy\TenantManager;

test('tenant can view list of own invoices', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $companyA->users()->attach($userA->id, ['role' => Role::OWNER->value]);

    $clientA = Client::factory()->create(['company_id' => $companyA->id]);
    $clientB = Client::factory()->create(['company_id' => $companyB->id]);

    Invoice::factory()->create([
        'company_id' => $companyA->id,
        'client_id' => $clientA->id,
        'number' => 'INV-0001',
    ]);
    Invoice::factory()->create([
        'company_id' => $companyB->id,
        'client_id' => $clientB->id,
        'number' => 'INV-9999',
    ]);

    $response = actingAsTenant($userA, $companyA)->get('/invoices');

    $response->assertOk();
    $response->assertSee('INV-0001');
    $response->assertDontSee('INV-9999');
});

test('user can create a draft invoice with items and calculated totals', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'unit_price' => 5000,
        'tax_rate' => 10,
    ]);

    $payload = [
        'client_id' => $client->id,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'notes' => 'Thank you for your business.',
        'items' => [
            [
                'product_id' => $product->id,
                'description' => 'Custom Consulting',
                'quantity' => 2,
                'unit_price' => 5000, // 50.00 * 2 = 100.00
                'tax_rate' => 10,     // 10% = 10.00
                'discount' => 0,
            ],
        ],
    ];

    $response = actingAsTenant($user, $company)->post('/invoices', $payload);

    $response->assertRedirect();

    $invoice = Invoice::where('company_id', $company->id)->first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->number)->toBe('INV-0001')
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and($invoice->subtotal)->toBe(10000)
        ->and($invoice->tax_total)->toBe(1000)
        ->and($invoice->total)->toBe(11000)
        ->and($invoice->balance())->toBe(11000)
        ->and($invoice->items)->toHaveCount(1);

    expect($invoice->items[0]->description)->toBe('Custom Consulting')
        ->and($invoice->items[0]->line_total)->toBe(11000);
});

test('user cannot create invoice with another company client or product', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $companyA->users()->attach($userA->id, ['role' => Role::OWNER->value]);

    $foreignClient = Client::factory()->create(['company_id' => $companyB->id]);
    $foreignProduct = Product::factory()->create(['company_id' => $companyB->id]);

    $payload = [
        'client_id' => $foreignClient->id,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'items' => [
            [
                'product_id' => $foreignProduct->id,
                'description' => 'Unauthorized item',
                'quantity' => 1,
                'unit_price' => 1000,
                'tax_rate' => 0,
                'discount' => 0,
            ],
        ],
    ];

    $response = actingAsTenant($userA, $companyA)->post('/invoices', $payload);

    $response->assertSessionHasErrors(['client_id', 'items.0.product_id']);
    expect(Invoice::count())->toBe(0);
});

test('user cannot update a non-draft invoice', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
    ]);

    $payload = [
        'client_id' => $client->id,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'items' => [
            [
                'description' => 'Changed description',
                'quantity' => 1,
                'unit_price' => 5000,
                'tax_rate' => 0,
                'discount' => 0,
            ],
        ],
    ];

    $response = actingAsTenant($user, $company)->put("/invoices/{$invoice->id}", $payload);

    $response->assertForbidden();
});

test('user can send, void, and duplicate an invoice', function () {
    $company = Company::factory()->create(['next_invoice_number' => 2]);
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::DRAFT,
        'number' => 'INV-0001',
    ]);
    $invoice->items()->create([
        'company_id' => $company->id,
        'description' => 'Original Item',
        'quantity' => 1,
        'unit_price' => 5000,
        'tax_rate' => 0,
        'discount' => 0,
        'line_total' => 5000,
    ]);

    // Send
    $sendResponse = actingAsTenant($user, $company)->post("/invoices/{$invoice->id}/send");
    $sendResponse->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);

    // Void
    $voidResponse = actingAsTenant($user, $company)->post("/invoices/{$invoice->id}/void", [
        'reason' => 'Customer cancelled the agreement.',
    ]);
    $voidResponse->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::VOID);

    // Duplicate
    $dupResponse = actingAsTenant($user, $company)->post("/invoices/{$invoice->id}/duplicate");
    $dupResponse->assertRedirect();

    $duplicated = Invoice::where('id', '!=', $invoice->id)->first();
    expect($duplicated)->not->toBeNull()
        ->and($duplicated->status)->toBe(InvoiceStatus::DRAFT)
        ->and($duplicated->number)->toBe('INV-0002')
        ->and($duplicated->items)->toHaveCount(1)
        ->and($duplicated->items[0]->description)->toBe('Original Item');
});

test('public invoice can be viewed without authentication using public token', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'public_token' => 'pub_tok_123456789abcdef',
    ]);

    $response = $this->get('/i/pub_tok_123456789abcdef');

    $response->assertOk();
    $response->assertSee($invoice->number);
    $response->assertSee($client->name);
});

test('user can view single invoice via route model binding without prior tenant initialization', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);
    $client = Client::factory()->create(['company_id' => $company->id]);

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'number' => 'INV-0010',
    ]);

    // Clear tenant context explicitly to test middleware resolution order
    app(TenantManager::class)->clear();

    $response = $this->actingAs($user)->get("/invoices/{$invoice->id}");

    $response->assertOk();
});
