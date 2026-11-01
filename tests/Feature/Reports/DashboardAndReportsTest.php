<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Events\InvoicePaid;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('dashboard kpi calculations match deterministic fixtures for owner', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00'));

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    $client = Client::factory()->create(['company_id' => $company->id]);

    // 1. Sent invoice: total 10,000, 0 paid, sent this month
    $invoiceSent = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::SENT,
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-30',
        'subtotal' => 10000,
        'total' => 10000,
        'amount_paid' => 0,
        'sent_at' => '2026-11-02 10:00:00',
    ]);

    // 2. Partially paid invoice: total 20,000, 5,000 paid, balance 15,000, sent this month
    $invoicePartial = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'issue_date' => '2026-11-05',
        'due_date' => '2026-11-25',
        'subtotal' => 20000,
        'total' => 20000,
        'amount_paid' => 5000,
        'sent_at' => '2026-11-06 09:00:00',
    ]);

    // Payment on the partially paid invoice (in November)
    Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoicePartial->id,
        'amount' => 5000,
        'paid_at' => '2026-11-08',
        'method' => PaymentMethod::BANK_TRANSFER,
    ]);

    // 3. Overdue invoice: total 15,000, 3,000 paid, balance 12,000, due in October
    $invoiceOverdue = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::OVERDUE,
        'issue_date' => '2026-10-01',
        'due_date' => '2026-10-31',
        'subtotal' => 15000,
        'total' => 15000,
        'amount_paid' => 3000,
        'sent_at' => '2026-10-02 10:00:00',
    ]);

    // Payment on the overdue invoice from last month (October) - should NOT count in November revenue
    Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoiceOverdue->id,
        'amount' => 3000,
        'paid_at' => '2026-10-15',
        'method' => PaymentMethod::CASH,
    ]);

    // 4. Paid invoice: total 8,000, fully paid this month (November)
    $invoicePaid = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::PAID,
        'issue_date' => '2026-11-02',
        'due_date' => '2026-11-10',
        'subtotal' => 8000,
        'total' => 8000,
        'amount_paid' => 8000,
        'sent_at' => '2026-11-03 10:00:00',
        'paid_at' => '2026-11-09 14:00:00',
    ]);

    Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoicePaid->id,
        'amount' => 8000,
        'paid_at' => '2026-11-09',
        'method' => PaymentMethod::STRIPE,
    ]);

    // 5. Void invoice: total 50,000 (must be completely ignored from revenue and outstanding)
    Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::VOID,
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-15',
        'subtotal' => 50000,
        'total' => 50000,
        'amount_paid' => 0,
    ]);

    // Expected KPI values:
    // Revenue this month = 5,000 (partial) + 8,000 (paid) = 13,000
    // Total outstanding = 10,000 (sent) + 15,000 (partial) + 12,000 (overdue) = 37,000
    // Overdue amount = 12,000
    // Overdue count = 1
    // Invoices sent this month = 3 (invoiceSent, invoicePartial, invoicePaid)

    $response = $this->actingAsTenant($user, $company)
        ->get(route('dashboard'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('kpis', fn (Assert $kpis) => $kpis
            ->where('revenue_this_month', 13000)
            ->where('total_outstanding', 37000)
            ->where('overdue_amount', 12000)
            ->where('overdue_count', 1)
            ->where('invoices_sent_this_month', 3)
            ->where('can_view_revenue', true)
        )
        ->has('recentInvoices')
        ->has('overdueInvoices', 1)
    );

    Carbon::setTestNow();
});

test('staff sees counts but revenue metrics are masked with null on dashboard', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00'));

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'staff']);

    $client = Client::factory()->create(['company_id' => $company->id]);

    Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::OVERDUE,
        'due_date' => '2026-11-01',
        'total' => 10000,
        'amount_paid' => 0,
        'sent_at' => '2026-11-02 10:00:00',
    ]);

    $response = $this->actingAsTenant($user, $company)
        ->get(route('dashboard'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('kpis', fn (Assert $kpis) => $kpis
            ->where('revenue_this_month', null)
            ->where('total_outstanding', null)
            ->where('overdue_amount', null)
            ->where('overdue_count', 1)
            ->where('invoices_sent_this_month', 1)
            ->where('can_view_revenue', false)
        )
    );

    Carbon::setTestNow();
});

test('reports page is accessible to owner and accountant but forbidden for staff', function () {
    $company = Company::factory()->create();

    $owner = User::factory()->create();
    $owner->companies()->attach($company->id, ['role' => 'owner']);

    $accountant = User::factory()->create();
    $accountant->companies()->attach($company->id, ['role' => 'accountant']);

    $staff = User::factory()->create();
    $staff->companies()->attach($company->id, ['role' => 'staff']);

    // Owner can view reports
    $this->actingAsTenant($owner, $company)
        ->get(route('reports.index'))
        ->assertSuccessful();

    // Accountant can view reports
    $this->actingAsTenant($accountant, $company)
        ->get(route('reports.index'))
        ->assertSuccessful();

    // Staff is forbidden
    $this->actingAsTenant($staff, $company)
        ->get(route('reports.index'))
        ->assertForbidden();
});

test('reports data aggregates revenue by month, status breakdown, top clients, and aging buckets with tenant isolation', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00'));

    $companyA = Company::factory()->create();
    $ownerA = User::factory()->create();
    $ownerA->companies()->attach($companyA->id, ['role' => 'owner']);

    $companyB = Company::factory()->create();
    $clientB = Client::factory()->create(['company_id' => $companyB->id]);
    Invoice::factory()->create([
        'company_id' => $companyB->id,
        'client_id' => $clientB->id,
        'total' => 999999,
        'issue_date' => '2026-11-01',
    ]);

    $clientA1 = Client::factory()->create(['company_id' => $companyA->id, 'name' => 'Acme Corp']);
    $clientA2 = Client::factory()->create(['company_id' => $companyA->id, 'name' => 'Beta LLC']);

    $invA1 = Invoice::factory()->create([
        'company_id' => $companyA->id,
        'client_id' => $clientA1->id,
        'status' => InvoiceStatus::PAID,
        'issue_date' => '2026-10-15',
        'due_date' => '2026-10-30',
        'total' => 20000,
        'amount_paid' => 20000,
    ]);

    Payment::factory()->create([
        'company_id' => $companyA->id,
        'invoice_id' => $invA1->id,
        'amount' => 20000,
        'paid_at' => '2026-10-20',
    ]);

    $invA2 = Invoice::factory()->create([
        'company_id' => $companyA->id,
        'client_id' => $clientA2->id,
        'status' => InvoiceStatus::SENT,
        'issue_date' => '2026-11-01',
        'due_date' => '2026-11-10', // 5 days past due -> in 0-30 days bucket
        'total' => 30000,
        'amount_paid' => 0,
    ]);

    $response = $this->actingAsTenant($ownerA, $companyA)
        ->get(route('reports.index', [
            'from' => '2026-10-01',
            'to' => '2026-11-30',
        ]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('reports/index')
        ->has('reportsData', fn (Assert $data) => $data
            ->has('revenue_by_month')
            ->has('status_breakdown')
            ->has('top_clients')
            ->has('aging_buckets')
        )
    );

    // Call service directly to verify exact figures and tenant isolation
    $reportService = app(ReportService::class);
    $data = $reportService->getReportsData($companyA->id, Carbon::parse('2026-10-01'), Carbon::parse('2026-11-30'));

    // Check top clients - Company B's 999999 invoice must not appear!
    expect($data['top_clients'])->toHaveCount(2);
    expect($data['top_clients'][0]['name'])->toBe('Beta LLC');
    expect($data['top_clients'][0]['total_billed'])->toBe(30000);
    expect($data['top_clients'][1]['name'])->toBe('Acme Corp');
    expect($data['top_clients'][1]['total_billed'])->toBe(20000);

    // Aging buckets: 0-30 days bucket has 30,000 from invA2
    $aging0to30 = collect($data['aging_buckets'])->firstWhere('bucket', '0-30 days');
    expect($aging0to30['amount'])->toBe(30000);
    expect($aging0to30['count'])->toBe(1);

    Carbon::setTestNow();
});

test('csv export streams invoices and payments correctly and forbids staff', function () {
    $company = Company::factory()->create();

    $owner = User::factory()->create();
    $owner->companies()->attach($company->id, ['role' => 'owner']);

    $staff = User::factory()->create();
    $staff->companies()->attach($company->id, ['role' => 'staff']);

    $client = Client::factory()->create(['company_id' => $company->id, 'name' => 'Export Client']);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'number' => 'INV-EXP-001',
        'total' => 15000,
        'amount_paid' => 15000,
        'status' => InvoiceStatus::PAID,
    ]);

    Payment::factory()->create([
        'company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'amount' => 15000,
        'paid_at' => '2026-11-01',
        'reference' => 'REF-EXP-999',
    ]);

    // Owner can export invoices CSV
    $invoicesCsv = $this->actingAsTenant($owner, $company)
        ->get(route('reports.export.invoices'));

    $invoicesCsv->assertSuccessful();
    $invoicesCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($invoicesCsv->streamedContent())->toContain('INV-EXP-001');
    expect($invoicesCsv->streamedContent())->toContain('Export Client');

    // Owner can export payments CSV
    $paymentsCsv = $this->actingAsTenant($owner, $company)
        ->get(route('reports.export.payments'));

    $paymentsCsv->assertSuccessful();
    $paymentsCsv->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($paymentsCsv->streamedContent())->toContain('REF-EXP-999');

    // Staff is forbidden from both exports
    $this->actingAsTenant($staff, $company)
        ->get(route('reports.export.invoices'))
        ->assertForbidden();

    $this->actingAsTenant($staff, $company)
        ->get(route('reports.export.payments'))
        ->assertForbidden();
});

test('dashboard cache is populated and busted on invoice and payment events', function () {
    $company = Company::factory()->create();
    $reportService = app(ReportService::class);

    // Initial stats should populate cache
    $cacheKey = "dashboard_stats_{$company->id}";
    expect(Cache::has($cacheKey))->toBeFalse();

    $reportService->getDashboardStats($company->id, true);
    expect(Cache::has($cacheKey))->toBeTrue();

    // Trigger event -> cache key should be forgotten
    $client = Client::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
    ]);

    event(new InvoicePaid($invoice));
    expect(Cache::has($cacheKey))->toBeFalse();
});
