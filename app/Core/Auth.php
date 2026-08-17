<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Connection;
use PDOException;

final class Auth
{
    private static ?array $user = null;

    public static function hasUsers(): bool
    {
        try {
            return (int) Connection::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    public static function attempt(string $identity, string $password): bool
    {
        $statement = Connection::connection()->prepare(
            'SELECT * FROM users WHERE (username = :identity OR email = :identity) AND active = 1 LIMIT 1'
        );
        $statement->execute(['identity' => $identity]);
        $user = $statement->fetch();
        if ($user === false || !Security::verifyPassword($password, (string) $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = $user['id'];
        self::$user = $user;
        try {
            $update = Connection::connection()->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = :id');
            $update->execute(['id' => $user['id']]);
        } catch (PDOException) {
            // Compatible con instalaciones que aún no ejecutan la migración maestra.
        }
        Audit::log('auth.login', 'users', (string) $user['id']);

        return true;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = $_SESSION['auth_user_id'] ?? null;
        if (!is_string($id) || $id === '') {
            return null;
        }

        $statement = Connection::connection()->prepare('SELECT * FROM users WHERE id = :id AND active = 1 LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        self::$user = $user === false ? null : $user;

        return self::$user;
    }

    public static function id(): ?string
    {
        return isset(self::user()['id']) ? (string) self::user()['id'] : null;
    }

    public static function companyId(): ?string
    {
        return isset(self::user()['company_id']) ? (string) self::user()['company_id'] : null;
    }

    public static function requireLogin(): void
    {
        if (!self::hasUsers()) {
            header('Location: ' . url('/setup'));
            exit;
        }
        if (self::user() === null) {
            $_SESSION['_flash']['error'] = 'Inicia sesión para continuar.';
            header('Location: ' . url('/login'));
            exit;
        }
    }

    public static function can(string $area, string $action = 'view'): bool
    {
        $role = (string) (self::user()['role'] ?? '');
        $roles = require BASE_PATH . '/config/roles.php';
        $grants = $roles[$role] ?? [];
        if (in_array('*', $grants, true)) {
            return true;
        }

        return in_array($area, $grants, true)
            || in_array($area . '.' . $action, $grants, true)
            || ($action === 'view' && in_array($area . '.own', $grants, true));
    }

    public static function requirePermission(string $area, string $action = 'view'): void
    {
        self::requireLogin();
        if (!self::can($area, $action)) {
            http_response_code(403);
            (new Controller())->view('errors/403', ['title' => 'Acceso restringido', 'activeMenu' => '']);
            exit;
        }
    }

    public static function logout(): void
    {
        if (self::id() !== null) {
            Audit::log('auth.logout', 'users', self::id());
        }
        unset($_SESSION['auth_user_id']);
        self::$user = null;
        session_regenerate_id(true);
    }
}
