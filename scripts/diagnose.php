<?php

declare(strict_types=1);

/**
 * Diagnóstico seguro para XAMPP/hosting. Solo funciona desde la consola.
 * No imprime contraseñas ni secretos del archivo .env.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$errors = 0;
$warnings = 0;

$line = static function (string $status, string $message): void {
    echo sprintf("[%s] %s%s", $status, $message, PHP_EOL);
};
$ok = static fn(string $message) => $line('OK', $message);
$warn = static function (string $message) use ($line, &$warnings): void {
    $warnings++;
    $line('AVISO', $message);
};
$fail = static function (string $message) use ($line, &$errors): void {
    $errors++;
    $line('ERROR', $message);
};

echo "BGV Enterprise · Diagnóstico XAMPP" . PHP_EOL;
echo str_repeat('=', 42) . PHP_EOL;

if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    $ok('PHP ' . PHP_VERSION . ' compatible.');
} else {
    $fail('PHP ' . PHP_VERSION . ' detectado; se requiere PHP 8.2 o superior.');
}

foreach (['pdo', 'pdo_mysql', 'fileinfo'] as $extension) {
    extension_loaded($extension)
        ? $ok('Extensión cargada: ' . $extension)
        : $fail('Falta la extensión PHP: ' . $extension);
}

$requiredFiles = [
    'index.php', '.htaccess', 'app/bootstrap.php', 'app/Database/Connection.php',
    'config/database/connection.php', 'config/modules.php', 'public/index.php',
    'database/01-schema.sql', 'database/03-documento-maestro-v1.sql',
];
foreach ($requiredFiles as $relativeFile) {
    is_file($root . '/' . $relativeFile)
        ? $ok('Archivo disponible: ' . $relativeFile)
        : $fail('Falta el archivo: ' . $relativeFile);
}

is_file($root . '/.env')
    ? $ok('Archivo .env disponible.')
    : $fail('Falta .env; copie .env.xampp.example como .env y configure MySQL.');

$logDirectory = $root . '/storage/logs';
if (!is_dir($logDirectory)) {
    $fail('No existe storage/logs.');
} elseif (!is_writable($logDirectory)) {
    $fail('storage/logs no tiene permiso de escritura para PHP/Apache.');
} else {
    $ok('storage/logs tiene permiso de escritura.');
}

try {
    require $root . '/app/bootstrap.php';
    $ok('Bootstrap y cargador de clases iniciados correctamente.');
} catch (Throwable $exception) {
    $fail(sprintf(
        'Fallo al iniciar bootstrap: %s: %s (%s:%d)',
        $exception::class,
        $exception->getMessage(),
        basename($exception->getFile()),
        $exception->getLine()
    ));
    echo PHP_EOL . "Resultado: {$errors} error(es), {$warnings} aviso(s)." . PHP_EOL;
    exit(1);
}

try {
    $config = require $root . '/config/database/connection.php';
    $ok(sprintf(
        'Configuración MySQL: host=%s, puerto=%s, base=%s.',
        (string) ($config['host'] ?? '(vacío)'),
        (string) ($config['port'] ?? '(vacío)'),
        (string) ($config['database'] ?? '(vacío)')
    ));
    if (($config['host'] ?? '') === 'db') {
        $warn('DB_HOST=db corresponde a Docker. En XAMPP normalmente debe ser 127.0.0.1.');
    }
} catch (Throwable $exception) {
    $fail('No fue posible leer la configuración MySQL: ' . $exception->getMessage());
}

try {
    $database = App\Database\Connection::connection();
    $version = (string) $database->query('SELECT VERSION()')->fetchColumn();
    $activeDatabase = (string) $database->query('SELECT DATABASE()')->fetchColumn();
    $ok("Conexión establecida con {$activeDatabase} en MySQL/MariaDB {$version}.");

    $tables = $database->query(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
    )->fetchAll(PDO::FETCH_COLUMN);
    $tableMap = array_fill_keys(array_map('strval', $tables), true);

    foreach (['companies', 'users', 'clients'] as $criticalTable) {
        isset($tableMap[$criticalTable])
            ? $ok('Tabla crítica disponible: ' . $criticalTable)
            : $fail('Falta la tabla crítica: ' . $criticalTable . '. Importe database/01-schema.sql.');
    }

    $modules = require $root . '/config/modules.php';
    $moduleTables = array_values(array_unique(array_map(
        static fn(array $module): string => (string) $module['table'],
        $modules
    )));
    $missingModuleTables = array_values(array_filter(
        $moduleTables,
        static fn(string $table): bool => !isset($tableMap[$table])
    ));
    if ($missingModuleTables === []) {
        $ok(count($moduleTables) . ' tablas funcionales del Documento Maestro disponibles.');
    } else {
        $fail(
            'Faltan tablas de database/03-documento-maestro-v1.sql: '
            . implode(', ', $missingModuleTables)
        );
    }

    if (isset($tableMap['users'])) {
        $columns = $database->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
        )->fetchAll(PDO::FETCH_COLUMN);
        $columnMap = array_fill_keys(array_map('strval', $columns), true);
        $requiredUserColumns = ['id', 'company_id', 'username', 'full_name', 'email', 'password_hash', 'role', 'active'];
        $missingColumns = array_values(array_filter(
            $requiredUserColumns,
            static fn(string $column): bool => !isset($columnMap[$column])
        ));
        $missingColumns === []
            ? $ok('Estructura de autenticación de users compatible.')
            : $fail('Faltan columnas en users: ' . implode(', ', $missingColumns));

        $userCount = (int) $database->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $superAdminCount = 0;
        if ($missingColumns === []) {
            $superAdminCount = (int) $database->query(
                "SELECT COUNT(*) FROM users WHERE role = 'Super Administrador' AND active = 1"
            )->fetchColumn();
        }
        $ok("Usuarios registrados: {$userCount}.");
        $superAdminCount > 0
            ? $ok('Existe un Super Administrador activo.')
            : $warn('No existe un Super Administrador activo; ejecute scripts/create-super-admin.php.');
    }
} catch (Throwable $exception) {
    $fail(sprintf(
        'Fallo MySQL: %s: %s (%s:%d)',
        $exception::class,
        $exception->getMessage(),
        basename($exception->getFile()),
        $exception->getLine()
    ));
}

echo PHP_EOL . "Resultado: {$errors} error(es), {$warnings} aviso(s)." . PHP_EOL;
exit($errors === 0 ? 0 : 1);
