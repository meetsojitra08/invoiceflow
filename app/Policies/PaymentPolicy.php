<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->currentRole(), [Role::OWNER->value, Role::ACCOUNTANT->value], true);
    }

    public function view(User $user, Payment $payment): bool
    {
        return in_array($user->currentRole(), [Role::OWNER->value, Role::ACCOUNTANT->value], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->currentRole(), [Role::OWNER->value, Role::ACCOUNTANT->value], true);
    }
}
