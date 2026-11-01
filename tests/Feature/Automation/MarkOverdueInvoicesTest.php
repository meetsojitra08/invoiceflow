<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoices past due date are marked as overdue', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-01 10:00:00'));

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    $client = Client::factory()->create(['company_id' => $company->id]);

    // Sent invoice past due
    $sentPastDue = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'due_date' => '2026-10-31',
    ]);

    // Partially paid invoice past due
    $partialPastDue = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'due_date' => '2026-10-25',
        'total' => 10000,
        'amount_paid' => 4000,
    ]);

    // Sent invoice due today (should NOT be marked overdue yet)
    $sentDueToday = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'due_date' => '2026-11-01',
    ]);

    // Sent invoice due tomorrow
    $sentDueFuture = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'due_date' => '2026-11-02',
    ]);

    // Draft invoice past due (drafts should remain draft)
    $draftPastDue = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::DRAFT,
        'due_date' => '2026-10-30',
    ]);

    // Paid invoice past due (should remain paid)
    $paidPastDue = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::PAID,
        'due_date' => '2026-10-20',
        'total' => 5000,
        'amount_paid' => 5000,
    ]);

    $this->artisan('invoices:mark-overdue')
        ->assertSuccessful();

    expect($sentPastDue->fresh()->status)->toBe(InvoiceStatus::OVERDUE);
    expect($partialPastDue->fresh()->status)->toBe(InvoiceStatus::OVERDUE);
    expect($sentDueToday->fresh()->status)->toBe(InvoiceStatus::SENT);
    expect($sentDueFuture->fresh()->status)->toBe(InvoiceStatus::SENT);
    expect($draftPastDue->fresh()->status)->toBe(InvoiceStatus::DRAFT);
    expect($paidPastDue->fresh()->status)->toBe(InvoiceStatus::PAID);

    Carbon::setTestNow();
});
