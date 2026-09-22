<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\HttpException;

// A tiny router: maps "METHOD + URL pattern" to a function.
//
//   $router->get('/files/(\d+)/download', fn (string $id) => ...);
//
// Each (...) group in the pattern is passed to the function as an argument.
class Router
{
    /** @var array<int, array{string, string, callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod === $method && preg_match('#^' . $pattern . '$#', $path, $matches)) {
                $handler(...array_slice($matches, 1));
                return;
            }
        }

        throw new HttpException(404);
    }
}
