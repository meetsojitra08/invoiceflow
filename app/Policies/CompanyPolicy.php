<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewTeam(User $user, Company $company): bool
    {
        return $user->roleIn($company) !== null;
    }

    public function manageTeam(User $user, Company $company): bool
    {
        return $user->roleIn($company) === Role::OWNER->value;
    }

    public function invite(User $user, Company $company): bool
    {
        return $this->manageTeam($user, $company);
    }

    public function updateMemberRole(User $user, Company $company, User $target): bool
    {
        if (! $this->manageTeam($user, $company)) {
            return false;
        }

        // Users cannot change their own role
        if ($user->id === $target->id) {
            return false;
        }

        return true;
    }

    public function removeMember(User $user, Company $company, User $target): bool
    {
        if (! $this->manageTeam($user, $company)) {
            return false;
        }

        // Users cannot remove themselves from team management (must use leave or another mechanism)
        if ($user->id === $target->id) {
            return false;
        }

        // If target is an owner, cannot remove if they are the last owner
        $targetRole = $target->roleIn($company);
        if ($targetRole === Role::OWNER->value && $company->ownersCount() <= 1) {
            return false;
        }

        return true;
    }
}
