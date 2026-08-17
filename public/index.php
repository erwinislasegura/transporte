<?php

declare(strict_types=1);

use App\Controllers\Api\ClientApiController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\ApiController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\ModuleController;
use App\Controllers\ReportController;
use App\Core\Cors;
use App\Core\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

Cors::handle();

$router = new Router();

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'authenticate']);
$router->get('/setup', [AuthController::class, 'setup']);
$router->post('/setup', [AuthController::class, 'initialize']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/export', [ReportController::class, 'export']);
$router->get('/clients', static fn() => header('Location: ' . url('/modules/clients')));
$router->get('/clients/create', static fn() => header('Location: ' . url('/modules/clients/create')));
$router->get('/clients/{id}', static fn(string $id) => header('Location: ' . url('/modules/clients/' . rawurlencode($id))));

$router->get('/modules/{slug}', [ModuleController::class, 'index']);
$router->get('/modules/{slug}/create', [ModuleController::class, 'create']);
$router->post('/modules/{slug}', [ModuleController::class, 'store']);
$router->get('/modules/{slug}/{id}', [ModuleController::class, 'show']);
$router->get('/modules/{slug}/{id}/edit', [ModuleController::class, 'edit']);
$router->post('/modules/{slug}/{id}', [ModuleController::class, 'update']);
$router->post('/modules/{slug}/{id}/archive', [ModuleController::class, 'archive']);
$router->get('/modules/{slug}/{id}/download', [ModuleController::class, 'download']);

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
        'diagnostic' => $debug ? $exception->getMessage() : null,
    ]);
}
