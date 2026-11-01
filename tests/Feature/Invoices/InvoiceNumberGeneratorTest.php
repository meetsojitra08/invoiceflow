<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use App\Models\Company;
use App\Services\Billing\InvoiceNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_sequential_padded_numbers(): void
    {
        $company = Company::factory()->create([
            'invoice_prefix' => 'INV',
            'next_invoice_number' => 1,
        ]);

        $generator = app(InvoiceNumberGenerator::class);

        $num1 = DB::transaction(fn () => $generator->generate($company->id));
        $num2 = DB::transaction(fn () => $generator->generate($company->id));
        $num3 = DB::transaction(fn () => $generator->generate($company->id));

        $this->assertSame('INV-0001', $num1);
        $this->assertSame('INV-0002', $num2);
        $this->assertSame('INV-0003', $num3);

        $this->assertSame(4, $company->fresh()->next_invoice_number);
    }

    public function test_respects_custom_company_prefix(): void
    {
        $company = Company::factory()->create([
            'invoice_prefix' => 'BILL',
            'next_invoice_number' => 42,
        ]);

        $generator = app(InvoiceNumberGenerator::class);

        $number = DB::transaction(fn () => $generator->generate($company->id));

        $this->assertSame('BILL-0042', $number);
        $this->assertSame(43, $company->fresh()->next_invoice_number);
    }

    public function test_independent_number_sequences_per_company(): void
    {
        $companyA = Company::factory()->create(['invoice_prefix' => 'A', 'next_invoice_number' => 1]);
        $companyB = Company::factory()->create(['invoice_prefix' => 'B', 'next_invoice_number' => 1]);

        $generator = app(InvoiceNumberGenerator::class);

        $numA1 = DB::transaction(fn () => $generator->generate($companyA->id));
        $numB1 = DB::transaction(fn () => $generator->generate($companyB->id));
        $numA2 = DB::transaction(fn () => $generator->generate($companyA->id));

        $this->assertSame('A-0001', $numA1);
        $this->assertSame('B-0001', $numB1);
        $this->assertSame('A-0002', $numA2);
    }
}
