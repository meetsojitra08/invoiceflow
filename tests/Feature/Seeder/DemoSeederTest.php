<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('demo seeder populates company, users, clients, products, invoices, and payments correctly', function () {
    $this->seed(DemoSeeder::class);

    // 1. Verify Company
    $company = Company::where('email', 'contact@democorp.io')->first();
    expect($company)->not->toBeNull();
    expect($company->name)->toBe('Demo Corp');

    // 2. Verify Users
    $owner = User::where('email', 'owner@demo.com')->first();
    expect($owner)->not->toBeNull();
    expect(Hash::check('password', $owner->password))->toBeTrue();
    expect($owner->currentRole())->toBe('owner');

    $accountant = User::where('email', 'accountant@demo.com')->first();
    expect($accountant)->not->toBeNull();
    expect($accountant->currentRole())->toBe('accountant');

    $staff = User::where('email', 'staff@demo.com')->first();
    expect($staff)->not->toBeNull();
    expect($staff->currentRole())->toBe('staff');

    // 3. Verify Clients & Products
    expect(Client::withoutGlobalScopes()->where('company_id', $company->id)->count())->toBe(10);
    expect(Product::withoutGlobalScopes()->where('company_id', $company->id)->count())->toBe(15);

    // 4. Verify Invoices & Payments
    expect(Invoice::withoutGlobalScopes()->where('company_id', $company->id)->count())->toBe(40);
    expect(Payment::withoutGlobalScopes()->where('company_id', $company->id)->count())->toBeGreaterThanOrEqual(18);

    // 5. Verify no N+1 lazy loading exceptions on lists and dashboard
    $this->actingAsTenant($owner, $company)
        ->get(route('dashboard'))
        ->assertSuccessful();

    $this->actingAsTenant($owner, $company)
        ->get(route('invoices.index'))
        ->assertSuccessful();

    $this->actingAsTenant($owner, $company)
        ->get(route('payments.index'))
        ->assertSuccessful();

    $this->actingAsTenant($owner, $company)
        ->get(route('clients.index'))
        ->assertSuccessful();

    $this->actingAsTenant($owner, $company)
        ->get(route('products.index'))
        ->assertSuccessful();
});
