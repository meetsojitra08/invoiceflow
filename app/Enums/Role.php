<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case OWNER = 'owner';
    case ACCOUNTANT = 'accountant';
    case STAFF = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ACCOUNTANT => 'Accountant',
            self::STAFF => 'Staff',
        };
    }
}
