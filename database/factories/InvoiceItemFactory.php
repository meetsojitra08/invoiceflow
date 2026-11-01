<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'description' => fake()->words(3, true),
            'quantity' => 1.0,
            'unit_price' => 100000,
            'tax_rate' => 18.00,
            'discount' => 0,
            'line_total' => 118000,
            'position' => 0,
        ];
    }
}
