<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\InvoiceStatus;
use DomainException;

class InvalidInvoiceTransition extends DomainException
{
    public static function make(InvoiceStatus $from, InvoiceStatus $to, string $reason = ''): self
    {
        $message = "Cannot transition invoice from {$from->label()} to {$to->label()}.";

        if ($reason !== '') {
            $message .= " Reason: {$reason}";
        }

        return new self($message);
    }
}
