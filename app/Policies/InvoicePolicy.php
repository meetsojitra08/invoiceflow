<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->isDraft();
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return in_array($user->currentRole(), [Role::OWNER->value, Role::ACCOUNTANT->value], true);
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return in_array($user->currentRole(), [Role::OWNER->value, Role::ACCOUNTANT->value], true);
    }

    public function duplicate(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->currentRole() === Role::OWNER->value && $invoice->isDraft();
    }
}
