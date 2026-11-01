<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d');
        $dueDate = fake()->dateTimeBetween($issueDate, '+30 days')->format('Y-m-d');

        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('####'),
            'status' => InvoiceStatus::DRAFT,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'currency' => 'INR',
            'subtotal' => 100000, // ₹1,000.00
            'discount_total' => 0,
            'tax_total' => 18000, // ₹180.00
            'total' => 118000, // ₹1,180.00
            'amount_paid' => 0,
            'notes' => fake()->sentence(),
            'public_token' => Str::random(40),
        ];
    }
}
