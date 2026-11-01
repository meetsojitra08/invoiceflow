<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can perform crud operations on products via api', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    Sanctum::actingAs($user, ['*']);

    // 1. Create product
    $createResponse = $this->postJson(route('api.v1.products.store'), [
        'name' => 'Backend Development',
        'description' => 'Hourly consulting services',
        'unit_price' => 150000, // minor units (₹1500.00)
        'tax_rate' => 18,
        'is_active' => true,
    ]);

    $createResponse->assertStatus(201);
    $createResponse->assertJsonPath('data.name', 'Backend Development');
    $createResponse->assertJsonPath('data.unit_price', 150000);
    $productId = $createResponse->json('data.id');

    // 2. List products
    $listResponse = $this->getJson(route('api.v1.products.index'));
    $listResponse->assertSuccessful();
    $listResponse->assertJsonCount(1, 'data');

    // 3. Show product
    $showResponse = $this->getJson(route('api.v1.products.show', ['product' => $productId]));
    $showResponse->assertSuccessful();
    $showResponse->assertJsonPath('data.name', 'Backend Development');

    // 4. Update product
    $updateResponse = $this->putJson(route('api.v1.products.update', ['product' => $productId]), [
        'name' => 'Senior Backend Consulting',
        'unit_price' => 200000,
        'tax_rate' => 18,
    ]);
    $updateResponse->assertSuccessful();
    $updateResponse->assertJsonPath('data.name', 'Senior Backend Consulting');
    $updateResponse->assertJsonPath('data.unit_price', 200000);

    // 5. Delete product
    $deleteResponse = $this->deleteJson(route('api.v1.products.destroy', ['product' => $productId]));
    $deleteResponse->assertStatus(204);

    expect(Product::withoutGlobalScopes()->find($productId)->trashed())->toBeTrue();
});

test('tenant isolation prevents reading or modifying products across companies', function () {
    $companyA = Company::factory()->create();
    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $userA->companies()->attach($companyA->id, ['role' => 'owner']);

    $companyB = Company::factory()->create();
    $productB = Product::factory()->create(['company_id' => $companyB->id, 'name' => 'Secret Product']);

    Sanctum::actingAs($userA, ['*']);

    $this->getJson(route('api.v1.products.show', ['product' => $productB->id]))
        ->assertStatus(404);

    $this->putJson(route('api.v1.products.update', ['product' => $productB->id]), ['name' => 'Changed'])
        ->assertStatus(404);

    $this->deleteJson(route('api.v1.products.destroy', ['product' => $productB->id]))
        ->assertStatus(404);
});
