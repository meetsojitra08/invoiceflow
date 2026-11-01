<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceSent;
use App\Exceptions\InvalidInvoiceTransition;
use App\Models\Client;
use App\Models\Company;
use App\Models\Product;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_draft_invoice_with_items_and_snapshots(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Original Product',
            'unit_price' => 100000,
            'tax_rate' => 18.00,
        ]);

        $service = app(InvoiceService::class);

        $invoice = $service->createDraft([
            'client_id' => $client->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'notes' => 'Thank you for your business.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Original Product Snapshot',
                    'quantity' => 2,
                    'unit_price' => 100000,
                    'tax_rate' => 18.00,
                    'discount' => 10000, // 200000 - 10000 = 190000 + 34200 tax = 224200
                ],
            ],
        ]);

        $this->assertSame(InvoiceStatus::DRAFT, $invoice->status);
        $this->assertSame('INV-0001', $invoice->number);
        $this->assertSame(200000, $invoice->subtotal);
        $this->assertSame(10000, $invoice->discount_total);
        $this->assertSame(34200, $invoice->tax_total);
        $this->assertSame(224200, $invoice->total);
        $this->assertNotNull($invoice->public_token);

        // Edit product price and verify invoice item snapshot is unchanged
        $product->update(['unit_price' => 500000, 'name' => 'Renamed Product']);
        $item = $invoice->fresh()->items->first();
        $this->assertSame(100000, $item->unit_price);
        $this->assertSame('Original Product Snapshot', $item->description);
    }

    public function test_updates_draft_invoice_recalculating_totals(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create(['company_id' => $company->id]);
        $service = app(InvoiceService::class);

        $invoice = $service->createDraft([
            'client_id' => $client->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'items' => [
                [
                    'description' => 'Initial Item',
                    'quantity' => 1,
                    'unit_price' => 50000,
                    'tax_rate' => 0,
                    'discount' => 0,
                ],
            ],
        ]);

        $this->assertSame(50000, $invoice->total);

        // Update invoice with new items
        $updated = $service->updateDraft($invoice, [
            'client_id' => $client->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-20',
            'items' => [
                [
                    'description' => 'Updated Item',
                    'quantity' => 3,
                    'unit_price' => 50000, // 150000
                    'tax_rate' => 10.0, // 15000
                    'discount' => 0,
                ],
            ],
        ]);

        $this->assertSame(150000, $updated->subtotal);
        $this->assertSame(15000, $updated->tax_total);
        $this->assertSame(165000, $updated->total);
        $this->assertCount(1, $updated->items);
        $this->assertSame('Updated Item', $updated->items->first()->description);
    }

    public function test_cannot_update_non_draft_invoice(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create(['company_id' => $company->id]);
        $service = app(InvoiceService::class);

        $invoice = $service->createDraft([
            'client_id' => $client->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'items' => [
                ['description' => 'Item', 'quantity' => 1, 'unit_price' => 1000, 'tax_rate' => 0],
            ],
        ]);

        $service->send($invoice);

        $this->expectException(InvalidInvoiceTransition::class);

        $service->updateDraft($invoice, [
            'items' => [
                ['description' => 'Malicious update', 'quantity' => 1, 'unit_price' => 10, 'tax_rate' => 0],
            ],
        ]);
    }

    public function test_send_invoice_updates_status_and_dispatches_event(): void
    {
        Event::fake([InvoiceSent::class]);

        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create(['company_id' => $company->id]);
        $service = app(InvoiceService::class);

        $invoice = $service->createDraft([
            'client_id' => $client->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 20000, 'tax_rate' => 0],
            ],
        ]);

        $sent = $service->send($invoice);

        $this->assertSame(InvoiceStatus::SENT, $sent->status);
        $this->assertNotNull($sent->sent_at);

        Event::assertDispatched(InvoiceSent::class, function (InvoiceSent $event) use ($sent) {
            return $event->invoice->id === $sent->id;
        });
    }

    public function test_void_invoice_sets_void_reason(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create(['company_id' => $company->id]);
        $service = app(InvoiceService::class);

        $invoice = $service->createDraft([
            'client_id' => $client->id,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 20000, 'tax_rate' => 0],
            ],
        ]);

        $voided = $service->void($invoice, 'Order cancelled by client');

        $this->assertSame(InvoiceStatus::VOID, $voided->status);
        $this->assertNotNull($voided->voided_at);
        $this->assertSame('Order cancelled by client', $voided->void_reason);
    }

    public function test_duplicate_invoice_creates_new_draft(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create(['company_id' => $company->id]);
        $service = app(InvoiceService::class);

        $original = $service->createDraft([
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'notes' => 'Original notes',
            'items' => [
                ['description' => 'Copyable Item', 'quantity' => 2, 'unit_price' => 50000, 'tax_rate' => 18.0, 'discount' => 5000],
            ],
        ]);

        $duplicate = $service->duplicate($original);

        $this->assertNotSame($original->id, $duplicate->id);
        $this->assertSame('INV-0002', $duplicate->number);
        $this->assertSame(InvoiceStatus::DRAFT, $duplicate->status);
        $this->assertSame($original->total, $duplicate->total);
        $this->assertSame('Original notes', $duplicate->notes);
        $this->assertNotSame($original->public_token, $duplicate->public_token);
        $this->assertCount(1, $duplicate->items);
    }
}
