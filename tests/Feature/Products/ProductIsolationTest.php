<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_access_or_modify_another_tenants_product(): void
    {
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $productB = Product::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Company B Widget',
        ]);

        $this->actingAsTenant(null, $companyA, 'owner');

        $response = $this->put(route('products.update', $productB), [
            'name' => 'Modified by Company A',
            'unit_price' => 1000,
            'tax_rate' => 0.00,
        ]);

        $response->assertNotFound();
        $this->assertSame('Company B Widget', $productB->fresh()->name);
    }

    public function test_duplicate_name_allowed_across_different_tenants(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Product::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Standard Support',
            'unit_price' => 50000,
            'tax_rate' => 18.00,
        ]);

        $this->actingAsTenant(null, $companyB, 'owner');

        $response = $this->post(route('products.store'), [
            'name' => 'Standard Support',
            'unit_price' => 75000,
            'tax_rate' => 18.00,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'company_id' => $companyB->id,
            'name' => 'Standard Support',
            'unit_price' => 75000,
        ]);
    }
}
