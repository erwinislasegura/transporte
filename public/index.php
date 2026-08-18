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
use App\Controllers\RoleController;
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

$router->get('/roles', [RoleController::class, 'index']);
$router->get('/roles/create', [RoleController::class, 'create']);
$router->post('/roles', [RoleController::class, 'store']);
$router->get('/roles/{id}/edit', [RoleController::class, 'edit']);
$router->post('/roles/{id}', [RoleController::class, 'update']);

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
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $host = explode(':', $host, 2)[0];
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $isLocalRequest = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        || in_array($remoteAddress, ['127.0.0.1', '::1'], true);
    $previousException = $exception->getPrevious();
    $isDatabaseError = $exception instanceof PDOException || $previousException instanceof PDOException;
    try {
        $incidentId = strtoupper(bin2hex(random_bytes(4)));
    } catch (Throwable) {
        $incidentId = strtoupper(substr(sha1(uniqid('', true)), 0, 8));
    }
    $logDirectory = BASE_PATH . '/storage/logs';
    if (is_dir($logDirectory) && is_writable($logDirectory)) {
        error_log(sprintf(
            "[%s] [%s] %s: %s en %s:%d\n%s\n",
            date(DATE_ATOM),
            $incidentId,
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ), 3, $logDirectory . '/app.log');
    }

    (new App\Core\Controller())->view('errors/500', [
        'title' => 'Error interno',
        'activeMenu' => '',
        'incidentId' => $incidentId,
        'operationalMessage' => $isDatabaseError
            ? 'No fue posible consultar la base de datos configurada. En cPanel revise DB_HOST, el nombre completo con prefijo, el usuario asignado a la base y sus privilegios.'
            : null,
        'diagnostic' => ($debug || $isLocalRequest) ? sprintf(
            '%s: %s (%s:%d)',
            $exception::class,
            $exception->getMessage(),
            basename($exception->getFile()),
            $exception->getLine()
        ) : null,
    ]);
}
