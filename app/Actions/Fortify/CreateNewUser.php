<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $hasInvite = ! empty($input['invitation_token']);
        $invitation = null;

        if ($hasInvite) {
            /** @var CompanyInvitation|null $invitation */
            $invitation = CompanyInvitation::withoutGlobalScopes()
                ->where('token', $input['invitation_token'])
                ->first();

            if (! $invitation || ! $invitation->isValid()) {
                throw ValidationException::withMessages([
                    'invitation_token' => ['The workspace invitation is invalid or has expired.'],
                ]);
            }
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'company_name' => [$hasInvite ? 'nullable' : 'required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'invitation_token' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($input, $invitation): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            if ($invitation !== null) {
                $company = $invitation->company;
                $company->users()->attach($user->id, ['role' => $invitation->role->value]);
                $user->forceFill(['current_company_id' => $company->id])->save();
                $invitation->update(['accepted_at' => now()]);

                return $user;
            }

            $company = Company::create([
                'name' => $input['company_name'],
                'email' => $input['email'],
                'currency' => 'INR',
                'invoice_prefix' => 'INV',
                'next_invoice_number' => 1,
            ]);

            $company->users()->attach($user->id, ['role' => Role::OWNER->value]);

            $user->forceFill(['current_company_id' => $company->id])->save();

            return $user;
        });
    }
}
