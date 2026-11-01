<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register_with_company(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'company_name' => 'Acme Invoicing',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        /** @var User $user */
        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertNotNull($user->current_company_id);

        /** @var Company $company */
        $company = Company::where('name', 'Acme Invoicing')->firstOrFail();
        $this->assertSame($company->id, $user->current_company_id);

        $this->assertDatabaseHas('company_user', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => 'owner',
        ]);
    }

    public function test_registration_requires_company_name(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'company_name' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['company_name']);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_registration_rolls_back_if_company_creation_fails(): void
    {
        Company::creating(function () {
            throw new \RuntimeException('Database failure on company creation');
        });

        try {
            $this->post(route('register.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'company_name' => 'Acme Invoicing',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        } catch (\RuntimeException $e) {
            $this->assertSame('Database failure on company creation', $e->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('companies', 0);
    }
}
