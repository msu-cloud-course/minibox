<?php
// Front controller: every request that is not a real file in public/ ends up here.

declare(strict_types=1);

// Built-in server (php -S): let it serve existing files such as css/app.css directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$root = dirname(__DIR__);

if (!is_file($root . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('Dependencies are missing. Run: composer install');
}

require $root . '/vendor/autoload.php';

(new App\App($root))->run();
