<?php

namespace Tests;

use App\Models\Company;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    public function actingAsTenant(?User $user = null, ?Company $company = null, string $role = 'owner'): static
    {
        $company ??= Company::factory()->create();

        $user ??= User::factory()->create([
            'current_company_id' => $company->id,
        ]);

        if (! $user->companies()->where('companies.id', $company->id)->exists()) {
            $company->users()->attach($user->id, ['role' => $role]);
        }

        if ($user->current_company_id !== $company->id) {
            $user->forceFill(['current_company_id' => $company->id])->save();
        }

        app(TenantManager::class)->set($company);

        return $this->actingAs($user);
    }
}
