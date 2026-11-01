<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;

test('owner can view team index and list members', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner->id, ['role' => Role::OWNER->value]);

    $staff = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($staff->id, ['role' => Role::STAFF->value]);

    $response = actingAsTenant($owner, $company)->get('/team');

    $response->assertOk();
    $response->assertSee($owner->name);
    $response->assertSee($staff->name);
});

test('owner can invite a teammate via copyable link', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner->id, ['role' => Role::OWNER->value]);

    $response = actingAsTenant($owner, $company)->post('/team/invite', [
        'email' => 'colleague@example.com',
        'role' => Role::ACCOUNTANT->value,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('invitation_url');

    $invitation = CompanyInvitation::where('company_id', $company->id)->first();
    expect($invitation)->not->toBeNull()
        ->and($invitation->email)->toBe('colleague@example.com')
        ->and($invitation->role)->toBe(Role::ACCOUNTANT)
        ->and($invitation->isExpired())->toBeFalse();
});

test('cannot invite an existing member of the company', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner->id, ['role' => Role::OWNER->value]);

    $member = User::factory()->create(['email' => 'existing@example.com']);
    $company->users()->attach($member->id, ['role' => Role::STAFF->value]);

    $response = actingAsTenant($owner, $company)->post('/team/invite', [
        'email' => 'existing@example.com',
        'role' => Role::STAFF->value,
    ]);

    $response->assertSessionHasErrors('email');
    expect(CompanyInvitation::count())->toBe(0);
});

test('owner cannot change their own role', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner->id, ['role' => Role::OWNER->value]);

    $response = actingAsTenant($owner, $company)->put("/team/members/{$owner->id}", [
        'role' => Role::STAFF->value,
    ]);

    $response->assertForbidden();
    expect($owner->roleIn($company))->toBe(Role::OWNER->value);
});

test('cannot demote or remove the last owner of a company', function () {
    $company = Company::factory()->create();
    $owner1 = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner1->id, ['role' => Role::OWNER->value]);

    $staff = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($staff->id, ['role' => Role::STAFF->value]);

    // Create a temporary second owner who will try to demote owner1
    $owner2 = User::factory()->create(['current_company_id' => $company->id]);
    $company->users()->attach($owner2->id, ['role' => Role::OWNER->value]);

    // Detach owner2 so only owner1 remains
    $company->users()->detach($owner2->id);

    // Try to demote owner1 using an API call as owner1 (fails self-edit)
    $selfDemote = actingAsTenant($owner1, $company)->put("/team/members/{$owner1->id}", [
        'role' => Role::STAFF->value,
    ]);
    $selfDemote->assertForbidden();

    // Re-attach owner2
    $company->users()->attach($owner2->id, ['role' => Role::OWNER->value]);

    // Now owner1 can update owner2 to staff
    $demoteRes = actingAsTenant($owner1, $company)->put("/team/members/{$owner2->id}", [
        'role' => Role::STAFF->value,
    ]);
    $demoteRes->assertRedirect();
    expect($owner2->roleIn($company))->toBe(Role::STAFF->value);

    // Now owner1 is the sole owner. owner2 cannot remove owner1 (owner2 is now staff so 403)
    actingAsTenant($owner2, $company)->delete("/team/members/{$owner1->id}")
        ->assertForbidden();
});

test('invited user can accept invitation and join the workspace', function () {
    $company = Company::factory()->create();
    $invitation = CompanyInvitation::factory()->create([
        'company_id' => $company->id,
        'email' => 'invited@example.com',
        'role' => Role::ACCOUNTANT,
        'token' => 'test-invitation-token-12345',
        'expires_at' => now()->addHours(48),
    ]);

    $user = User::factory()->create(['email' => 'invited@example.com']);

    $response = $this->actingAs($user)->post("/invitations/{$invitation->token}/accept");

    $response->assertRedirect('/dashboard');
    expect($user->roleIn($company))->toBe(Role::ACCOUNTANT->value)
        ->and($user->fresh()->current_company_id)->toBe($company->id)
        ->and($invitation->fresh()->isAccepted())->toBeTrue();
});

test('cannot accept invitation if logged in with different email', function () {
    $company = Company::factory()->create();
    $invitation = CompanyInvitation::factory()->create([
        'company_id' => $company->id,
        'email' => 'target@example.com',
        'role' => Role::STAFF,
        'token' => 'target-token-12345',
        'expires_at' => now()->addHours(48),
    ]);

    $wrongUser = User::factory()->create(['email' => 'someoneelse@example.com']);

    $response = $this->actingAs($wrongUser)->post("/invitations/{$invitation->token}/accept");

    $response->assertSessionHasErrors('error');
    expect($wrongUser->roleIn($company))->toBeNull()
        ->and($invitation->fresh()->isAccepted())->toBeFalse();
});

test('expired invitation cannot be accepted', function () {
    $company = Company::factory()->create();
    $invitation = CompanyInvitation::factory()->create([
        'company_id' => $company->id,
        'email' => 'expired@example.com',
        'role' => Role::STAFF,
        'token' => 'expired-token-12345',
        'expires_at' => now()->subDay(),
    ]);

    $user = User::factory()->create(['email' => 'expired@example.com']);

    $response = $this->actingAs($user)->post("/invitations/{$invitation->token}/accept");

    $response->assertSessionHasErrors('error');
    expect($user->roleIn($company))->toBeNull();
});
