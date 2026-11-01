<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('api endpoints throttle at 60 requests per minute and return 429', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->forceFill(['current_company_id' => $company->id])->save();

    Sanctum::actingAs($user, ['*']);

    // Send 60 requests - all should succeed
    for ($i = 0; $i < 60; $i++) {
        $this->getJson(route('api.v1.clients.index'))
            ->assertSuccessful();
    }

    // 61st request should be throttled with HTTP 429 Too Many Requests
    $throttled = $this->getJson(route('api.v1.clients.index'));
    $throttled->assertStatus(429);
    $throttled->assertHeader('Retry-After');
});
