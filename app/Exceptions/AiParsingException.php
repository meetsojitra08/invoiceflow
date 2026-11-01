<?php

declare(strict_types=1);

namespace App\Exceptions;

class AiParsingException extends AiException
{
    public function __construct(string $message = 'Could not understand the invoice details. Please try rephrasing with clearer items and prices.')
    {
        parent::__construct($message);
    }
}
