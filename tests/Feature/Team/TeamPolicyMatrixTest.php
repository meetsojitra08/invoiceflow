<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;

dataset('roles_matrix', [
    'owner' => [Role::OWNER, true, true, true, true],
    'accountant' => [Role::ACCOUNTANT, false, true, true, false],
    'staff' => [Role::STAFF, false, false, false, false],
]);

test('enforces role permission matrix for company resources', function (
    Role $role,
    bool $canDeleteClient,
    bool $canManageProducts,
    bool $canSendInvoice,
    bool $canManageTeam
) {
    $company = Company::factory()->create();
    $user = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($user->id, ['role' => $role->value]);

    $client = Client::factory()->create(['company_id' => $company->id]);
    $product = Product::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    // 1. Client delete
    $deleteClientRes = actingAsTenant($user, $company)->delete("/clients/{$client->id}");
    if ($canDeleteClient) {
        $deleteClientRes->assertRedirect();
    } else {
        $deleteClientRes->assertForbidden();
    }

    // 2. Product create
    $productRes = actingAsTenant($user, $company)->post('/products', [
        'name' => 'Matrix Test Product',
        'unit_price' => 1000,
        'tax_rate' => 0,
    ]);
    if ($canManageProducts) {
        $productRes->assertRedirect();
    } else {
        $productRes->assertForbidden();
    }

    // 3. Invoice send
    $invoiceSendRes = actingAsTenant($user, $company)->post("/invoices/{$invoice->id}/send");
    if ($canSendInvoice) {
        $invoiceSendRes->assertRedirect();
    } else {
        $invoiceSendRes->assertForbidden();
    }

    // 4. Team invite
    $teamInviteRes = actingAsTenant($user, $company)->post('/team/invite', [
        'email' => 'newteammate@example.com',
        'role' => Role::STAFF->value,
    ]);
    if ($canManageTeam) {
        $teamInviteRes->assertRedirect();
    } else {
        $teamInviteRes->assertForbidden();
    }
})->with('roles_matrix');
