<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_to_another_company_they_belong_to(): void
    {
        $company1 = Company::factory()->create(['name' => 'Company One']);
        $company2 = Company::factory()->create(['name' => 'Company Two']);

        $user = User::factory()->create([
            'current_company_id' => $company1->id,
        ]);

        $company1->users()->attach($user->id, ['role' => 'owner']);
        $company2->users()->attach($user->id, ['role' => 'accountant']);

        $response = $this->actingAs($user)->post(route('companies.switch'), [
            'company_id' => $company2->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertSame($company2->id, $user->fresh()->current_company_id);
    }

    public function test_user_cannot_switch_to_a_company_they_do_not_belong_to(): void
    {
        $company1 = Company::factory()->create(['name' => 'Company One']);
        $foreignCompany = Company::factory()->create(['name' => 'Foreign Company']);

        $user = User::factory()->create([
            'current_company_id' => $company1->id,
        ]);
        $company1->users()->attach($user->id, ['role' => 'owner']);

        $response = $this->actingAs($user)->post(route('companies.switch'), [
            'company_id' => $foreignCompany->id,
        ]);

        $response->assertForbidden();
        $this->assertSame($company1->id, $user->fresh()->current_company_id);
    }
}
