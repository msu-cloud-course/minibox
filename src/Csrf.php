<?php

declare(strict_types=1);

namespace App;

// Protection against CSRF: every form sends a secret token that only our pages know.
// A form on another website cannot know it, so its requests are rejected.
class Csrf
{
    public function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function isValid(mixed $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
