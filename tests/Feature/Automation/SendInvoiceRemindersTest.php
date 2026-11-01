<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Events\InvoiceSent;
use App\Events\PaymentRecorded;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('reminders are sent at 3, 7, and 14 days after due date and are idempotent', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'email' => 'client@example.com',
        'send_reminders' => true,
    ]);

    // Test Day 3 milestone
    Carbon::setTestNow(Carbon::parse('2026-11-04 09:00:00'));

    $invoice3Days = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::OVERDUE,
        'due_date' => '2026-11-01', // exactly 3 days overdue
        'total' => 10000,
        'amount_paid' => 0,
    ]);

    $this->artisan('invoices:send-reminders')
        ->assertSuccessful();

    Mail::assertSent(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($invoice3Days) {
        return $mail->invoice->id === $invoice3Days->id && $mail->daysOverdue === 3;
    });

    expect(InvoiceReminder::withoutGlobalScopes()->where('invoice_id', $invoice3Days->id)->where('days_overdue', 3)->exists())->toBeTrue();

    // Re-running command immediately should not send another reminder for milestone 3
    Mail::fake();
    $this->artisan('invoices:send-reminders')
        ->assertSuccessful();
    Mail::assertNothingSent();

    // Advance time to Day 7 milestone
    Carbon::setTestNow(Carbon::parse('2026-11-08 09:00:00')); // 7 days overdue
    Mail::fake();

    $this->artisan('invoices:send-reminders')
        ->assertSuccessful();

    Mail::assertSent(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($invoice3Days) {
        return $mail->invoice->id === $invoice3Days->id && $mail->daysOverdue === 7;
    });

    expect(InvoiceReminder::withoutGlobalScopes()->where('invoice_id', $invoice3Days->id)->where('days_overdue', 7)->exists())->toBeTrue();

    // Advance time to Day 14 milestone
    Carbon::setTestNow(Carbon::parse('2026-11-15 09:00:00')); // 14 days overdue
    Mail::fake();

    $this->artisan('invoices:send-reminders')
        ->assertSuccessful();

    Mail::assertSent(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($invoice3Days) {
        return $mail->invoice->id === $invoice3Days->id && $mail->daysOverdue === 14;
    });

    expect(InvoiceReminder::withoutGlobalScopes()->where('invoice_id', $invoice3Days->id)->where('days_overdue', 14)->exists())->toBeTrue();

    Carbon::setTestNow();
});

test('reminders are skipped if client opted out or invoice is paid', function () {
    Mail::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-05 09:00:00'));

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    // Opted out client
    $optedOutClient = Client::factory()->create([
        'company_id' => $company->id,
        'email' => 'optout@example.com',
        'send_reminders' => false,
    ]);

    $optedOutInvoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $optedOutClient->id,
        'status' => InvoiceStatus::OVERDUE,
        'due_date' => '2026-11-01',
        'total' => 10000,
        'amount_paid' => 0,
    ]);

    // Paid invoice
    $normalClient = Client::factory()->create([
        'company_id' => $company->id,
        'email' => 'normal@example.com',
        'send_reminders' => true,
    ]);

    $paidInvoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $normalClient->id,
        'status' => InvoiceStatus::PAID,
        'due_date' => '2026-11-01',
        'total' => 10000,
        'amount_paid' => 10000,
    ]);

    $this->artisan('invoices:send-reminders')
        ->assertSuccessful();

    Mail::assertNothingSent();
    expect(InvoiceReminder::withoutGlobalScopes()->count())->toBe(0);

    Carbon::setTestNow();
});

test('dashboard cache is busted when invoice or payment events fire', function () {
    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    // Test InvoiceSent
    Cache::put("dashboard_stats_{$company->id}", ['total' => 100], 60);
    event(new InvoiceSent($invoice));
    expect(Cache::has("dashboard_stats_{$company->id}"))->toBeFalse();

    // Test InvoicePaid
    Cache::put("dashboard_stats_{$company->id}", ['total' => 200], 60);
    event(new InvoicePaid($invoice));
    expect(Cache::has("dashboard_stats_{$company->id}"))->toBeFalse();

    // Test PaymentRecorded
    $payment = Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
    ]);
    Cache::put("dashboard_stats_{$company->id}", ['total' => 300], 60);
    event(new PaymentRecorded($payment));
    expect(Cache::has("dashboard_stats_{$company->id}"))->toBeFalse();
});
