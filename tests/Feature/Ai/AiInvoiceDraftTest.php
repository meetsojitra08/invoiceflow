<?php

declare(strict_types=1);

use App\Models\AiRequest;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('generates valid invoice draft with client matching and minor unit conversions', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    $existingClient = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Acme Corporation',
    ]);

    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'client_name' => 'Acme Corp',
                                    'due_in_days' => 20,
                                    'items' => [
                                        [
                                            'description' => 'UI/UX Redesign',
                                            'quantity' => 10,
                                            'unit_price' => 1500.50, // major units
                                            'tax_rate' => 18,
                                        ],
                                        [
                                            'description' => 'Backend API Work',
                                            'quantity' => 5,
                                            'unit_price' => 2000.00,
                                            'tax_rate' => 18,
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'totalTokenCount' => 150,
            ],
        ], 200),
    ]);

    $response = $this->actingAsTenant($user, $company)
        ->postJson(route('invoices.ai-draft'), [
            'prompt' => 'Invoice Acme Corp for 10 hours of UI/UX at 1500.50 and 5 hours of Backend at 2000, due in 20 days',
        ]);

    $response->assertSuccessful();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('draft.client_id', $existingClient->id);
    $response->assertJsonPath('draft.client_name', 'Acme Corp');
    $response->assertJsonCount(2, 'draft.items');

    // Assert prices converted to integer minor units (1500.50 -> 150050)
    expect($response->json('draft.items.0.unit_price'))->toBe(150050);
    expect($response->json('draft.items.0.quantity'))->toEqual(10);
    expect($response->json('draft.items.1.unit_price'))->toBe(200000);

    // Assert logged to ai_requests table
    expect(AiRequest::withoutGlobalScopes()->where('company_id', $company->id)->count())->toBe(1);
    $log = AiRequest::withoutGlobalScopes()->where('company_id', $company->id)->first();
    expect($log->status)->toBe('success');
    expect($log->tokens_used)->toBe(150);
});

test('returns 422 when feature flag is disabled', function () {
    config(['features.ai' => false]);

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    $response = $this->actingAsTenant($user, $company)
        ->postJson(route('invoices.ai-draft'), [
            'prompt' => 'Invoice Client for consulting 1000',
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('success', false);
    expect($response->json('message'))->toContain('disabled');
});

test('returns 422 when daily cap is exceeded', function () {
    config(['features.ai' => true]);
    config(['services.ai.daily_cap' => 3]);

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    // Seed 3 existing requests for today
    for ($i = 0; $i < 3; $i++) {
        AiRequest::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'feature' => 'invoice_draft',
            'model' => 'test-model',
            'status' => 'success',
            'created_at' => Carbon::now(),
        ]);
    }

    $response = $this->actingAsTenant($user, $company)
        ->postJson(route('invoices.ai-draft'), [
            'prompt' => 'Invoice Client for consulting 1000',
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('success', false);
    expect($response->json('message'))->toContain('Daily AI limit reached');

    // Assert throttled status logged
    expect(AiRequest::withoutGlobalScopes()->where('status', 'throttled')->count())->toBe(1);
});

test('invalid json response from ai is handled gracefully', function () {
    config(['features.ai' => true]);

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'I cannot generate an invoice for you right now.'],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAsTenant($user, $company)
        ->postJson(route('invoices.ai-draft'), [
            'prompt' => 'gibberish text without invoice details',
        ]);

    $response->assertStatus(422);
    $response->assertJsonPath('success', false);
    expect($response->json('message'))->toContain('Could not understand');

    // Assert failed request logged
    expect(AiRequest::withoutGlobalScopes()->where('status', 'failed')->count())->toBe(1);
});

test('client is never auto-created and unknown client has null id with matched candidates', function () {
    config(['features.ai' => true]);

    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company->id, ['role' => 'owner']);

    Http::fake([
        '*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'client_name' => 'Non Existent Brand Ltd',
                                    'due_in_days' => 14,
                                    'items' => [
                                        [
                                            'description' => 'Security Audit',
                                            'quantity' => 1,
                                            'unit_price' => 5000,
                                            'tax_rate' => 18,
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $clientCountBefore = Client::withoutGlobalScopes()->count();

    $response = $this->actingAsTenant($user, $company)
        ->postJson(route('invoices.ai-draft'), [
            'prompt' => 'Invoice Non Existent Brand Ltd for Security Audit 5000',
        ]);

    $response->assertSuccessful();
    expect($response->json('draft.client_id'))->toBeNull();
    expect($response->json('draft.client_name'))->toBe('Non Existent Brand Ltd');

    // Assert no client was automatically created in the database
    expect(Client::withoutGlobalScopes()->count())->toBe($clientCountBefore);
});
