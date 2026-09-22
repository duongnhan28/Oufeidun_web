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

use App\Core\Router;

$router = new Router();
require BASE_PATH . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
