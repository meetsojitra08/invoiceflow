<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Web Design',
            'unit_price' => 150000,
        ]);
        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'SEO Audit',
            'unit_price' => 50000,
        ]);

        $response = $this->get(route('products.index', ['search' => 'Web']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Web Design')
        );
    }

    public function test_accountant_can_create_product_with_price_in_minor_units(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company, 'accountant');

        $response = $this->post(route('products.store'), [
            'name' => 'Software Consulting',
            'description' => 'Hourly consulting rate',
            'unit_price' => 250000, // ₹2,500.00 in minor units
            'tax_rate' => 18.00,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'company_id' => $company->id,
            'name' => 'Software Consulting',
            'unit_price' => 250000,
            'tax_rate' => 18.00,
        ]);
    }

    public function test_product_name_must_be_unique_within_company(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company, 'owner');

        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Duplicate Product',
        ]);

        $response = $this->post(route('products.store'), [
            'name' => 'Duplicate Product',
            'unit_price' => 10000,
            'tax_rate' => 5.00,
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_staff_cannot_create_or_update_product(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company, 'staff');

        $createResponse = $this->post(route('products.store'), [
            'name' => 'Staff Product',
            'unit_price' => 10000,
            'tax_rate' => 18.00,
        ]);
        $createResponse->assertForbidden();

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Existing Product',
        ]);

        $updateResponse = $this->put(route('products.update', $product), [
            'name' => 'Hacked Product',
            'unit_price' => 99999,
            'tax_rate' => 18.00,
        ]);
        $updateResponse->assertForbidden();
    }

    public function test_owner_can_delete_product(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company, 'owner');

        $product = Product::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
