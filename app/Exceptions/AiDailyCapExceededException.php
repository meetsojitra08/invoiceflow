<?php

declare(strict_types=1);

namespace App\Exceptions;

class AiDailyCapExceededException extends AiException
{
    public function __construct(string $message = 'Daily AI limit reached for your workspace. Please try again tomorrow or create manually.')
    {
        parent::__construct($message);
    }
}
