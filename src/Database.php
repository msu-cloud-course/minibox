<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

// A small wrapper around PDO (PostgreSQL).
// The connection is opened on first use, so the error page still works when the database is down.
class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Config $config)
    {
    }

    public function host(): string
    {
        return $this->config->get('DB_HOST', '127.0.0.1');
    }

    public function connect(): PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->host(),
                $this->config->get('DB_PORT', '5432'),
                $this->config->get('DB_NAME', 'minibox')
            );

            $this->pdo = new PDO($dsn, $this->config->get('DB_USER', 'minibox'), $this->config->get('DB_PASSWORD', 'secret'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // SQL errors throw exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // rows as ['column' => value]
                PDO::ATTR_TIMEOUT => 5,                            // fail fast if the database is down
            ]);
        }

        return $this->pdo;
    }

    // Run a query with "?" placeholders. The values are sent separately from the SQL,
    // so user input can never change the query (no SQL injection).
    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    // Run SQL that can contain several statements (used for database/schema.sql).
    public function exec(string $sql): void
    {
        $this->connect()->exec($sql);
    }

    // A short hint for the two most common setup mistakes, or null for other errors.
    public static function problemHint(Throwable $e): ?string
    {
        if (!$e instanceof PDOException) {
            return null;
        }

        $message = $e->getMessage();
        if (str_contains($message, 'SQLSTATE[42P01]')) {   // table does not exist
            return 'The database has no tables yet. Run: php bin/init-db.php';
        }
        if (str_contains($message, 'SQLSTATE[08')) {       // connection errors
            return 'Cannot connect to the database. Check DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD.';
        }
        return null;
    }
}
