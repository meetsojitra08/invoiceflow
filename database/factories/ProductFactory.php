<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'unit_price' => fake()->numberBetween(50000, 500000), // ₹500 to ₹5000
            'tax_rate' => 18.00,
            'is_active' => true,
        ];
    }
}

