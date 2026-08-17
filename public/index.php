<?php

declare(strict_types=1);

use App\Controllers\Api\ClientApiController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\ApiController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Core\Cors;
use App\Core\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

Cors::handle();

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/clients', [ClientController::class, 'index']);
$router->get('/clients/create', [ClientController::class, 'create']);
$router->post('/clients', [ClientController::class, 'store']);
$router->get('/clients/{id}', [ClientController::class, 'show']);

$router->get('/api/v1', [ApiController::class, 'index']);
$router->get('/api/v1/docs', [ApiController::class, 'docs']);
$router->get('/api/v1/openapi.json', [ApiController::class, 'openApi']);
$router->get('/api/v1/health', [HealthController::class, 'show']);
$router->get('/api/v1/clients', [ClientApiController::class, 'index']);
$router->post('/api/v1/clients', [ClientApiController::class, 'store']);
$router->get('/api/v1/clients/{id}', [ClientApiController::class, 'show']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $exception) {
    http_response_code(500);
    $logDirectory = BASE_PATH . '/storage/logs';
    if (is_dir($logDirectory) && is_writable($logDirectory)) {
        error_log(sprintf("[%s] %s\n%s\n", date(DATE_ATOM), $exception->getMessage(), $exception->getTraceAsString()), 3, $logDirectory . '/app.log');
    }

    (new App\Core\Controller())->view('errors/500', [
        'title' => 'Error interno',
        'activeMenu' => '',
    ]);
}
