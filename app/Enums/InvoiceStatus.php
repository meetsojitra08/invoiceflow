<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::VOID => 'Void',
        };
    }

    public function canTransitionTo(self $target, int $amountPaid = 0): bool
    {
        if ($this === $target) {
            return false;
        }

        return match ($this) {
            self::DRAFT => in_array($target, [self::SENT, self::VOID], true),
            self::SENT => match ($target) {
                self::PARTIALLY_PAID, self::PAID, self::OVERDUE => true,
                self::VOID => $amountPaid === 0,
                default => false,
            },
            self::PARTIALLY_PAID => in_array($target, [self::PAID, self::OVERDUE], true),
            self::OVERDUE => in_array($target, [self::PARTIALLY_PAID, self::PAID], true),
            self::PAID, self::VOID => false,
        };
    }
}
