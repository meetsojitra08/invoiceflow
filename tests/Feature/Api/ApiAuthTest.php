<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can exchange email and password for api token', function () {
    $user = User::factory()->create([
        'email' => 'developer@example.com',
        'password' => bcrypt('secret123'),
    ]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    $response = $this->postJson(route('api.v1.auth.token'), [
        'email' => 'developer@example.com',
        'password' => 'secret123',
        'device_name' => 'CI Pipeline',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'token',
        'user' => ['id', 'name', 'email'],
        'company' => ['id', 'name'],
        'role',
    ]);
    expect($response->json('role'))->toBe('owner');
    expect($response->json('company.id'))->toBe($company->id);
});

test('invalid credentials return validation error', function () {
    $user = User::factory()->create([
        'email' => 'developer@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson(route('api.v1.auth.token'), [
        'email' => 'developer@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('staff members cannot generate api tokens', function () {
    $user = User::factory()->create([
        'email' => 'staff@example.com',
        'password' => bcrypt('secret123'),
    ]);
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'staff']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    $response = $this->postJson(route('api.v1.auth.token'), [
        'email' => 'staff@example.com',
        'password' => 'secret123',
    ]);

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Staff');
});

test('authenticated user can revoke current api token', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    $token = $user->createToken('test-token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->deleteJson(route('api.v1.auth.revoke'));

    $response->assertSuccessful();
    expect($user->tokens()->count())->toBe(0);
});

test('settings api tokens page allows owner to create and revoke tokens', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    // View tokens page
    $this->actingAsTenant($user, $company)
        ->get(route('settings.api-tokens.index'))
        ->assertSuccessful();

    // Create token
    $response = $this->actingAsTenant($user, $company)
        ->post(route('settings.api-tokens.store'), [
            'name' => 'Web App Integration',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('newToken');
    expect($user->tokens()->count())->toBe(1);

    $tokenId = $user->tokens()->first()->id;

    // Revoke token
    $revokeResponse = $this->actingAsTenant($user, $company)
        ->delete(route('settings.api-tokens.destroy', ['token' => $tokenId]));

    $revokeResponse->assertRedirect();
    expect($user->tokens()->count())->toBe(0);
});
