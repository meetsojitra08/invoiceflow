<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_manager_throws_when_no_tenant_context_is_set(): void
    {
        $manager = app(TenantManager::class);
        $manager->clear();

        try {
            $manager->idOrFail();
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('No tenant context set.', $e->getMessage());
        }
    }

    public function test_tenant_manager_scoped_execution_restores_previous_tenant(): void
    {
        $manager = app(TenantManager::class);
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $manager->set($company1);
        $this->assertSame($company1->id, $manager->id());

        $result = $manager->execute($company2, function (int $id) use ($company2) {
            $this->assertSame($company2->id, $id);

            return 'executed';
        });

        $this->assertSame('executed', $result);
        $this->assertSame($company1->id, $manager->id());
    }

    public function test_inertia_shares_tenant_details_for_authenticated_user(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Global']);
        $user = User::factory()->create(['current_company_id' => $company->id]);
        $company->users()->attach($user->id, ['role' => 'owner']);

        $this->actingAsTenant($user, $company);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('auth.company.id', $company->id)
            ->where('auth.company.name', 'Acme Global')
            ->where('auth.role', 'owner')
            ->has('auth.companies', 1)
        );
    }
}
