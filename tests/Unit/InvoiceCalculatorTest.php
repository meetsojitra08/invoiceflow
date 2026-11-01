<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Billing\InvoiceCalculator;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    private InvoiceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new InvoiceCalculator;
    }

    public function test_empty_items_returns_zero_totals(): void
    {
        $result = $this->calculator->calculate([]);

        $this->assertSame(0, $result['subtotal']);
        $this->assertSame(0, $result['discount_total']);
        $this->assertSame(0, $result['tax_total']);
        $this->assertSame(0, $result['total']);
        $this->assertEmpty($result['items']);
    }

    public function test_single_item_without_tax_or_discount(): void
    {
        $result = $this->calculator->calculate([
            [
                'description' => 'Simple service',
                'quantity' => 2,
                'unit_price' => 5000, // ₹50.00 each
                'tax_rate' => 0,
                'discount' => 0,
            ],
        ]);

        $this->assertSame(10000, $result['subtotal']);
        $this->assertSame(0, $result['discount_total']);
        $this->assertSame(0, $result['tax_total']);
        $this->assertSame(10000, $result['total']);

        $item = $result['items'][0];
        $this->assertSame(10000, $item['line_subtotal']);
        $this->assertSame(0, $item['line_tax']);
        $this->assertSame(10000, $item['line_total']);
    }

    public function test_single_item_with_percentage_tax_rounded_half_up(): void
    {
        // 1 item * 333 paise = 333 paise.
        // 15% tax on 333 = 49.95 -> rounds half up to 50 paise.
        $result = $this->calculator->calculate([
            [
                'description' => 'Rounding test item',
                'quantity' => 1,
                'unit_price' => 333,
                'tax_rate' => 15.0,
                'discount' => 0,
            ],
        ]);

        $this->assertSame(333, $result['subtotal']);
        $this->assertSame(0, $result['discount_total']);
        $this->assertSame(50, $result['tax_total']);
        $this->assertSame(383, $result['total']);

        $item = $result['items'][0];
        $this->assertSame(50, $item['line_tax']);
        $this->assertSame(383, $item['line_total']);
    }

    public function test_single_item_with_discount(): void
    {
        // 1 item @ ₹1000 (100000) with ₹200 (20000) discount and 18% tax.
        // Taxable = 80000. 18% of 80000 = 14400. Total = 80000 + 14400 = 94400.
        $result = $this->calculator->calculate([
            [
                'description' => 'Discounted consulting',
                'quantity' => 1,
                'unit_price' => 100000,
                'tax_rate' => 18.0,
                'discount' => 20000,
            ],
        ]);

        $this->assertSame(100000, $result['subtotal']);
        $this->assertSame(20000, $result['discount_total']);
        $this->assertSame(14400, $result['tax_total']);
        $this->assertSame(94400, $result['total']);
    }

    public function test_discount_is_capped_at_line_gross(): void
    {
        // Line gross is 5000, discount entered is 8000 -> capped at 5000.
        $result = $this->calculator->calculate([
            [
                'description' => 'Over-discounted',
                'quantity' => 1,
                'unit_price' => 5000,
                'tax_rate' => 18.0,
                'discount' => 8000,
            ],
        ]);

        $this->assertSame(5000, $result['subtotal']);
        $this->assertSame(5000, $result['discount_total']);
        $this->assertSame(0, $result['tax_total']);
        $this->assertSame(0, $result['total']);
    }

    public function test_fractional_quantities(): void
    {
        // 2.5 hours * ₹1,500.00 (150000) = 375000.
        // 18% tax on 375000 = 67500. Total = 442500.
        $result = $this->calculator->calculate([
            [
                'description' => 'Development hours',
                'quantity' => 2.5,
                'unit_price' => 150000,
                'tax_rate' => 18.0,
                'discount' => 0,
            ],
        ]);

        $this->assertSame(375000, $result['subtotal']);
        $this->assertSame(0, $result['discount_total']);
        $this->assertSame(67500, $result['tax_total']);
        $this->assertSame(442500, $result['total']);
    }

    public function test_multiple_items_with_varying_taxes_and_discounts(): void
    {
        $result = $this->calculator->calculate([
            [
                'description' => 'Item 1',
                'quantity' => 2,
                'unit_price' => 10000, // 20000
                'tax_rate' => 18.0, // 3600 tax
                'discount' => 0,
            ],
            [
                'description' => 'Item 2',
                'quantity' => 1,
                'unit_price' => 50000, // 50000 - 5000 = 45000
                'tax_rate' => 5.0, // 2250 tax
                'discount' => 5000,
            ],
        ]);

        $this->assertSame(70000, $result['subtotal']);
        $this->assertSame(5000, $result['discount_total']);
        $this->assertSame(5850, $result['tax_total']); // 3600 + 2250
        $this->assertSame(70850, $result['total']); // 70000 - 5000 + 5850
    }
}
