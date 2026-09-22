<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

// Base class for the models: gives every model access to the database.
// App calls Model::useDatabase() once at start-up.
abstract class Model
{
    private static ?Database $db = null;

    public static function useDatabase(Database $db): void
    {
        self::$db = $db;
    }

    protected static function db(): Database
    {
        return self::$db ?? throw new \LogicException('Call Model::useDatabase() first.');
    }
}
