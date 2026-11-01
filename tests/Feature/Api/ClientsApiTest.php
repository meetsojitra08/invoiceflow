<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can list, create, view, update, and delete clients via api', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    Sanctum::actingAs($user, ['*']);

    // 1. Create client
    $createResponse = $this->postJson(route('api.v1.clients.store'), [
        'name' => 'Acme Technologies',
        'email' => 'contact@acme.test',
        'phone' => '+91 9876543210',
        'billing_address' => '123 Tech Park, Bangalore',
        'tax_number' => 'GSTIN12345',
        'send_reminders' => true,
    ]);

    $createResponse->assertStatus(201);
    $createResponse->assertJsonPath('data.name', 'Acme Technologies');
    $createResponse->assertJsonPath('data.email', 'contact@acme.test');
    $clientId = $createResponse->json('data.id');

    // 2. List clients
    $listResponse = $this->getJson(route('api.v1.clients.index'));
    $listResponse->assertSuccessful();
    $listResponse->assertJsonCount(1, 'data');
    expect($listResponse->json('data.0.id'))->toBe($clientId);

    // 3. Show client
    $showResponse = $this->getJson(route('api.v1.clients.show', ['client' => $clientId]));
    $showResponse->assertSuccessful();
    $showResponse->assertJsonPath('data.name', 'Acme Technologies');

    // 4. Update client
    $updateResponse = $this->putJson(route('api.v1.clients.update', ['client' => $clientId]), [
        'name' => 'Acme Technologies Pvt Ltd',
        'email' => 'billing@acme.test',
    ]);
    $updateResponse->assertSuccessful();
    $updateResponse->assertJsonPath('data.name', 'Acme Technologies Pvt Ltd');

    // 5. Delete client
    $deleteResponse = $this->deleteJson(route('api.v1.clients.destroy', ['client' => $clientId]));
    $deleteResponse->assertStatus(204);

    expect(Client::withoutGlobalScopes()->find($clientId)->trashed())->toBeTrue();
});

test('tenant cannot view or manipulate another company client via api', function () {
    $companyA = Company::factory()->create();
    $userA = User::factory()->create(['current_company_id' => $companyA->id]);
    $userA->companies()->attach($companyA->id, ['role' => 'owner']);

    $companyB = Company::factory()->create();
    $userB = User::factory()->create(['current_company_id' => $companyB->id]);
    $userB->companies()->attach($companyB->id, ['role' => 'owner']);

    $clientB = Client::factory()->create(['company_id' => $companyB->id, 'name' => 'Secret Client']);

    Sanctum::actingAs($userA, ['*']);

    // Attempting to access Company B's client returns 404
    $this->getJson(route('api.v1.clients.show', ['client' => $clientB->id]))
        ->assertStatus(404);

    $this->putJson(route('api.v1.clients.update', ['client' => $clientB->id]), ['name' => 'Hacked'])
        ->assertStatus(404);

    $this->deleteJson(route('api.v1.clients.destroy', ['client' => $clientB->id]))
        ->assertStatus(404);
});
