<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST', $pattern, $handler); }
    public function put(string $pattern, callable $handler): void { $this->add('PUT', $pattern, $handler); }
    public function delete(string $pattern, callable $handler): void { $this->add('DELETE', $pattern, $handler); }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $normalized = $pattern === '/' ? '/' : rtrim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $normalized);
        $this->routes[] = [$method, '#^' . $regex . '$#', $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $method = strtoupper($method);
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod !== $method || !preg_match($regex, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler($params);
            return;
        }

        http_response_code(404);
        view('pages/404', ['title' => 'Không tìm thấy trang']);
    }
}

