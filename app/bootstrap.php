<?php

declare(strict_types=1);

use App\Core\Env;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
        return;
    }

    // Recupera instalaciones copiadas parcialmente durante la separación
    // de la conexión. El archivo nuevo sigue siendo la fuente principal.
    if ($class === 'App\\Database\\Connection') {
        $legacyFile = BASE_PATH . '/app/Core/Database.php';
        if (is_file($legacyFile)) {
            require_once $legacyFile;
            if (!class_exists($class, false)) class_alias(App\Core\Database::class, $class);
        }
    }
});

require BASE_PATH . '/app/helpers.php';

Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Santiago'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('bgv_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'use_strict_mode' => true,
    ]);
}

$debug = filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);
