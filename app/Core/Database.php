<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Compatibilidad para instalaciones que todavía referencian App\Core\Database.
 * La aplicación nueva utiliza App\Database\Connection.
 *
 * @deprecated Usar App\Database\Connection.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) return self::$connection;

        $modernConfig = BASE_PATH . '/config/database/connection.php';
        $legacyConfig = BASE_PATH . '/config/database.php';
        $configFile = is_file($modernConfig) ? $modernConfig : $legacyConfig;
        if (!is_file($configFile)) {
            throw new \RuntimeException('No se encontró la configuración de conexión MySQL.');
        }
        $config = require $configFile;
        if (!is_array($config)) {
            throw new \RuntimeException('La configuración MySQL no es válida.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'], $config['port'], $config['database'], $config['charset']
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
