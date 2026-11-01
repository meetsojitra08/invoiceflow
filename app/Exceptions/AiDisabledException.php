<?php

declare(strict_types=1);

namespace App\Exceptions;

class AiDisabledException extends AiException
{
    public function __construct(string $message = 'AI features are currently disabled.')
    {
        parent::__construct($message);
    }
}
