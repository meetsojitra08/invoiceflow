<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Client $client): bool
    {
        return true;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->currentRole() === Role::OWNER->value;
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->currentRole() === Role::OWNER->value;
    }
}
