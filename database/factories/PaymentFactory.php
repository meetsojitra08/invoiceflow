<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => 50000, // ₹500.00
            'currency' => 'INR',
            'method' => PaymentMethod::BANK_TRANSFER,
            'reference' => 'TXN-'.fake()->numerify('######'),
            'paid_at' => now()->toDateString(),
            'notes' => fake()->sentence(),
        ];
    }
}

