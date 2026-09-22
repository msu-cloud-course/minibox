<?php
// Creates the database table. Safe to run many times.
//   php bin/init-db.php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

try {
    $app = new App\App($root);
    $app->db->exec(file_get_contents($root . '/database/schema.sql'));
    echo 'Database is ready (' . $app->db->host() . ").\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    $hint = App\Database::problemHint($e);
    if ($hint !== null) {
        fwrite(STDERR, $hint . "\n");
    }
    exit(1);
}
