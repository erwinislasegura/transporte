<?php

declare(strict_types=1);

use App\Core\Env;

$connectionConfig = __DIR__ . '/database/connection.php';

if (is_file($connectionConfig)) {
    return require $connectionConfig;
}

// Compatibilidad con instalaciones que todavía no copiaron config/database/.
return [
    'driver' => Env::get('DB_CONNECTION', 'mysql'),
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'bgv_enterprise'),
    'username' => Env::get('DB_USERNAME', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
];
