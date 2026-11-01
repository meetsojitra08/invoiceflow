<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Events\InvoiceSent;
use App\Jobs\SendInvoiceEmail;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

test('authenticated user can download invoice pdf', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'number' => 'INV-0042',
    ]);
    $invoice->items()->create([
        'company_id' => $company->id,
        'description' => 'Web Design Services',
        'quantity' => 1,
        'unit_price' => 50000,
        'tax_rate' => 18,
        'discount' => 0,
        'line_total' => 59000,
    ]);

    $response = actingAsTenant($user, $company)->get("/invoices/{$invoice->id}/pdf");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('INV-0042.pdf');
});

test('guest can download public invoice pdf using valid token', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'number' => 'INV-0099',
        'public_token' => 'pub_tok_download_pdf_valid',
    ]);
    $invoice->items()->create([
        'company_id' => $company->id,
        'description' => 'Security Audit',
        'quantity' => 1,
        'unit_price' => 80000,
        'tax_rate' => 0,
        'discount' => 0,
        'line_total' => 80000,
    ]);

    $response = $this->get('/i/pub_tok_download_pdf_valid/pdf');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('INV-0099.pdf');
});

test('guest downloading pdf with invalid token returns 404', function () {
    $response = $this->get('/i/non_existent_token/pdf');

    $response->assertNotFound();
});

test('sending draft invoice dispatches queued email job and updates status', function () {
    Queue::fake();
    Event::fake([InvoiceSent::class]);

    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'email' => 'client@acme.com',
    ]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $response = actingAsTenant($user, $company)->post("/invoices/{$invoice->id}/send", [
        'recipient_email' => 'billing@acme.com',
        'message' => 'Please find your invoice attached.',
    ]);

    $response->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT)
        ->and($invoice->fresh()->sent_at)->not->toBeNull();

    Queue::assertPushed(SendInvoiceEmail::class, function (SendInvoiceEmail $job) use ($invoice) {
        return $job->invoice->id === $invoice->id
            && $job->recipientEmail === 'billing@acme.com'
            && $job->customMessage === 'Please find your invoice attached.';
    });

    Event::assertDispatched(InvoiceSent::class);
});

test('queued SendInvoiceEmail job delivers InvoiceMail with pdf attachment', function () {
    Mail::fake();

    $company = Company::factory()->create(['name' => 'Acme Labs']);
    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Global Corp']);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'number' => 'INV-0500',
    ]);
    $invoice->items()->create([
        'company_id' => $company->id,
        'description' => 'Cloud Hosting',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 0,
        'discount' => 0,
        'line_total' => 10000,
    ]);

    $job = new SendInvoiceEmail($invoice, 'finance@global.com', 'Here is your bill');
    $job->handle(app(TenantManager::class));

    Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($invoice) {
        return $mail->hasTo('finance@global.com')
            && $mail->invoice->id === $invoice->id
            && count($mail->attachments()) > 0;
    });
});

test('resending sent invoice sends email without resetting status', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

    $client = Client::factory()->create(['company_id' => $company->id, 'email' => 'client@acme.com']);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
    ]);

    $response = actingAsTenant($user, $company)->post("/invoices/{$invoice->id}/send");

    $response->assertRedirect();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);

    Queue::assertPushed(SendInvoiceEmail::class);
});

test('staff user cannot send invoice', function () {
    $company = Company::factory()->create();
    $staff = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($staff->id, ['role' => Role::STAFF->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $response = actingAsTenant($staff, $company)->post("/invoices/{$invoice->id}/send");

    $response->assertForbidden();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::DRAFT);
});

test('cannot download pdf of another company invoice', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $companyA->users()->attach($userA->id, ['role' => Role::OWNER->value]);

    $clientB = Client::factory()->create(['company_id' => $companyB->id]);
    $invoiceB = Invoice::factory()->create([
        'company_id' => $companyB->id,
        'client_id' => $clientB->id,
    ]);

    $response = actingAsTenant($userA, $companyA)->get("/invoices/{$invoiceB->id}/pdf");

    $response->assertNotFound();
});
