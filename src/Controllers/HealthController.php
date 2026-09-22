<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use Throwable;

// GET /health: a quick "is the app working?" check for monitoring tools.
// 200 = the app can read its database table, 500 = it cannot. "hostname" shows which machine answered.
class HealthController
{
    public function __construct(private readonly Database $db)
    {
    }

    public function check(): void
    {
        try {
            $this->db->query('SELECT COUNT(*) FROM files');
            $status = 200;
            $body = ['status' => 'ok', 'db' => 'ok'];
        } catch (Throwable $e) {
            error_log('Health check failed: ' . $e->getMessage());
            $status = 500;
            $body = ['status' => 'error', 'db' => 'error', 'hint' => Database::problemHint($e)];
        }

        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($body + ['hostname' => gethostname()], JSON_UNESCAPED_SLASHES);
    }
}
