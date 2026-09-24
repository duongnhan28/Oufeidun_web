<?php
declare(strict_types=1);

// PHP's built-in server sends every request through this router file. Let it
// serve real CSS, JavaScript, image and video files directly, just like Apache.
if (PHP_SAPI === 'cli-server') {
    $requestPath = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    $publicRoot = realpath(__DIR__);
    $requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $requestPath), DIRECTORY_SEPARATOR));
    if ($requestPath !== '/' && $publicRoot !== false && $requestedFile !== false && is_file($requestedFile) && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Env;
use App\Core\Router;

$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (str_starts_with($requestPath, '/api/app/v1')) {
    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    $allowedOrigins = array_values(array_filter(array_map(
        'trim',
        explode(',', Env::get('APP_API_ALLOWED_ORIGINS', 'shop://app,http://127.0.0.1:5178') ?? '')
    )));

    if ($origin !== '') {
        if (!in_array($origin, $allowedOrigins, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo '{"error":"ORIGIN_NOT_ALLOWED"}';
            exit;
        }
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 600');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

$router = new Router();
require BASE_PATH . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
