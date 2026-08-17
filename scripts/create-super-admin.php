<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Id;
use App\Core\Security;
use App\Database\Connection;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

function option(string $name, string $default): string
{
    $value = trim((string) Env::get($name, ''));
    return $value === '' ? $default : $value;
}

function secretPrompt(): string
{
    $fromEnvironment = (string) Env::get('SUPER_ADMIN_PASSWORD', '');
    if ($fromEnvironment !== '') return $fromEnvironment;

    fwrite(STDOUT, "Contraseña inicial del Super Administrador: ");
    $hidden = DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');
    if ($hidden) shell_exec('stty -echo');
    $password = trim((string) fgets(STDIN));
    if ($hidden) {
        shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
    }
    return $password;
}

$companyRut = option('SUPER_ADMIN_COMPANY_RUT', '76.000.000-0');
$companyName = option('SUPER_ADMIN_COMPANY_NAME', 'BGV Enterprise SpA');
$tradeName = option('SUPER_ADMIN_TRADE_NAME', 'BGV Enterprise');
$username = option('SUPER_ADMIN_USERNAME', 'superadmin');
$fullName = option('SUPER_ADMIN_FULL_NAME', 'Super Administrador BGV');
$email = option('SUPER_ADMIN_EMAIL', 'admin@bgventerprise.cl');
$password = secretPrompt();

if (strlen($password) < 12) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres." . PHP_EOL);
    exit(1);
}

$db = Connection::connection();
try {
    $db->beginTransaction();

    $statement = $db->prepare('SELECT id FROM companies WHERE rut = :rut LIMIT 1');
    $statement->execute(['rut' => $companyRut]);
    $companyId = $statement->fetchColumn();
    if (!is_string($companyId) || $companyId === '') {
        $companyId = Id::uuid();
        $statement = $db->prepare(
            'INSERT INTO companies (id, rut, legal_name, trade_name, active)
             VALUES (:id, :rut, :legal_name, :trade_name, 1)'
        );
        $statement->execute(['id'=>$companyId, 'rut'=>$companyRut, 'legal_name'=>$companyName, 'trade_name'=>$tradeName]);
    } else {
        $statement = $db->prepare(
            'UPDATE companies SET legal_name = :legal_name, trade_name = :trade_name, active = 1 WHERE id = :id'
        );
        $statement->execute(['id'=>$companyId, 'legal_name'=>$companyName, 'trade_name'=>$tradeName]);
    }

    $statement = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $statement->execute(['username'=>$username, 'email'=>$email]);
    $userId = $statement->fetchColumn();
    $passwordHash = Security::hashPassword($password);

    if (!is_string($userId) || $userId === '') {
        $userId = Id::uuid();
        $statement = $db->prepare(
            'INSERT INTO users (id, company_id, username, full_name, email, password_hash, role, active)
             VALUES (:id, :company_id, :username, :full_name, :email, :password_hash, :role, 1)'
        );
        $statement->execute([
            'id'=>$userId, 'company_id'=>$companyId, 'username'=>$username, 'full_name'=>$fullName,
            'email'=>$email, 'password_hash'=>$passwordHash, 'role'=>'Super Administrador',
        ]);
    } else {
        $statement = $db->prepare(
            'UPDATE users SET company_id = :company_id, username = :username, full_name = :full_name,
             email = :email, password_hash = :password_hash, role = :role, active = 1 WHERE id = :id'
        );
        $statement->execute([
            'id'=>$userId, 'company_id'=>$companyId, 'username'=>$username, 'full_name'=>$fullName,
            'email'=>$email, 'password_hash'=>$passwordHash, 'role'=>'Super Administrador',
        ]);
    }

    $db->commit();
    fwrite(STDOUT, "Empresa y Super Administrador creados correctamente." . PHP_EOL);
    fwrite(STDOUT, "Empresa: {$tradeName} ({$companyRut})" . PHP_EOL);
    fwrite(STDOUT, "Usuario: {$username}" . PHP_EOL);
    fwrite(STDOUT, "Correo: {$email}" . PHP_EOL);
} catch (Throwable $exception) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "No fue posible crear la cuenta: {$exception->getMessage()}" . PHP_EOL);
    exit(1);
}
