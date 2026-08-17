<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Punto único de acceso a MySQL/MariaDB.
 *
 * La clase vive fuera de Core para mantener la infraestructura de datos
 * desacoplada del router, autenticación y controladores MVC.
 */
final class Connection
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $modernConfig = BASE_PATH . '/config/database/connection.php';
        $legacyConfig = BASE_PATH . '/config/database.php';
        $configFile = is_file($modernConfig) ? $modernConfig : $legacyConfig;
        if (!is_file($configFile)) {
            throw new \RuntimeException('No se encontró config/database/connection.php. Copia todas las carpetas de la actualización.');
        }
        $config = require $configFile;
        if (!is_array($config)) {
            throw new \RuntimeException('La configuración de conexión MySQL no devolvió un arreglo válido.');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$connection->exec("SET time_zone = '+00:00'");

        return self::$connection;
    }
}
