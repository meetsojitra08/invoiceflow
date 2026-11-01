<?php

declare(strict_types=1);

use App\Models\User;

test('authenticated user can calculate invoice preview totals', function () {
    $user = User::factory()->create();

    $response = actingAsTenant($user)->postJson('/invoices/calculate', [
        'items' => [
            [
                'quantity' => 2,
                'unit_price' => 10000, // 100.00 each -> 200.00
                'tax_rate' => 10,       // 10% tax -> 190.00 * 10% = 19.00 -> 1900
                'discount' => 1000,     // 10.00 discount -> taxable 19000
            ],
            [
                'quantity' => 1,
                'unit_price' => 5000,  // 50.00 each -> 50.00
                'tax_rate' => 0,
                'discount' => 0,
            ],
        ],
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['subtotal'])->toBe(25000)
        ->and($data['discount_total'])->toBe(1000)
        ->and($data['tax_total'])->toBe(1900)
        ->and($data['total'])->toBe(25900) // 25000 - 1000 + 1900
        ->and($data['items'])->toHaveCount(2);

    expect($data['items'][0]['line_total'])->toBe(20900); // 19000 + 1900
    expect($data['items'][1]['line_total'])->toBe(5000);
});

test('calculate endpoint validates item structures', function () {
    $user = User::factory()->create();

    $response = actingAsTenant($user)->postJson('/invoices/calculate', [
        'items' => [
            [
                'quantity' => -1,
                'unit_price' => -50,
                'tax_rate' => 150,
                'discount' => -10,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'items.0.quantity',
        'items.0.unit_price',
        'items.0.tax_rate',
        'items.0.discount',
    ]);
});
