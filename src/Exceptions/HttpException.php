<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

// Throw this to stop the request and show an error page, e.g. throw new HttpException(404).
class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status)
    {
        parent::__construct("HTTP $status");
    }
}
