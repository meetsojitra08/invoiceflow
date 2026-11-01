<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InvalidPaymentException extends RuntimeException
{
    public static function forStatus(string $status): self
    {
        return new self("Cannot record payment on invoice with status '{$status}'.");
    }

    public static function exceedsBalance(int $amount, int $balance): self
    {
        return new self("Payment amount ({$amount}) exceeds the remaining balance ({$balance}).");
    }

    public static function nonPositiveAmount(): self
    {
        return new self('Payment amount must be greater than zero.');
    }
}
