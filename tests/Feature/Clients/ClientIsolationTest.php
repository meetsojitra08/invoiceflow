<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_access_another_tenants_client(): void
    {
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $clientB = Client::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Secret Client of B',
        ]);

        $this->actingAsTenant(null, $companyA);

        $response = $this->get(route('clients.show', $clientB));

        $response->assertNotFound();
    }

    public function test_duplicate_email_allowed_across_different_tenants(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Client::factory()->create([
            'company_id' => $companyA->id,
            'email' => 'shared@customer.com',
        ]);

        $this->actingAsTenant(null, $companyB);

        $response = $this->post(route('clients.store'), [
            'name' => 'Customer at Company B',
            'email' => 'shared@customer.com',
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'company_id' => $companyB->id,
            'email' => 'shared@customer.com',
        ]);
    }
}
