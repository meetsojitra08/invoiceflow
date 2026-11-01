<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_clients_with_search(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
        ]);
        Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'Stark Industries',
            'email' => 'tony@stark.com',
        ]);

        $response = $this->get(route('clients.index', ['search' => 'Acme']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('clients/index')
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Acme Corporation')
        );
    }

    public function test_can_create_client(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $response = $this->post(route('clients.store'), [
            'name' => 'Wayne Enterprises',
            'email' => 'bruce@wayne.com',
            'phone' => '+1 555-0199',
            'billing_address' => '1007 Mountain Drive, Gotham',
            'tax_number' => 'GSTIN1234567890',
            'send_reminders' => true,
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('clients', [
            'company_id' => $company->id,
            'name' => 'Wayne Enterprises',
            'email' => 'bruce@wayne.com',
        ]);
    }

    public function test_client_email_must_be_unique_within_same_company(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        Client::factory()->create([
            'company_id' => $company->id,
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->post(route('clients.store'), [
            'name' => 'Another Client',
            'email' => 'duplicate@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_can_view_client_details(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'Oscorp Industries',
        ]);

        $response = $this->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('clients/show')
            ->where('client.id', $client->id)
            ->where('client.name', 'Oscorp Industries')
        );
    }

    public function test_can_update_client(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company);

        $client = Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'Old Name',
        ]);

        $response = $this->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'send_reminders' => false,
        ]);

        $response->assertRedirect(route('clients.index'));
        $this->assertSame('Updated Name', $client->fresh()->name);
        $this->assertFalse($client->fresh()->send_reminders);
    }

    public function test_owner_can_archive_and_restore_client(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company, 'owner');

        $client = Client::factory()->create([
            'company_id' => $company->id,
        ]);

        // Archive (soft delete)
        $response = $this->delete(route('clients.destroy', $client));
        $response->assertRedirect(route('clients.index'));
        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        // Restore
        $restoreResponse = $this->post(route('clients.restore', $client->id));
        $restoreResponse->assertRedirect(route('clients.index'));
        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_staff_cannot_archive_client(): void
    {
        $company = Company::factory()->create();
        $this->actingAsTenant(null, $company, 'staff');

        $client = Client::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->delete(route('clients.destroy', $client));

        $response->assertForbidden();
        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
    }
}
