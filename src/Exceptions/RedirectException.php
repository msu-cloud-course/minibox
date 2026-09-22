<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

// Throw this to stop the request and send the browser to another page.
class RedirectException extends RuntimeException
{
    public function __construct(public readonly string $url)
    {
        parent::__construct("Redirect to $url");
    }
}
