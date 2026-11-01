<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case STRIPE = 'stripe';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH => 'Cash',
            self::CREDIT_CARD => 'Credit Card',
            self::STRIPE => 'Stripe',
            self::OTHER => 'Other',
        };
    }
}
