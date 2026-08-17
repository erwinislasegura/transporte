<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;
use App\Core\Security;

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $statement = Connection::connection()->prepare(
            'SELECT * FROM users WHERE username = :username AND active = 1 LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public static function hashPassword(string $password): string
    {
        return Security::hashPassword($password);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return Security::verifyPassword($password, $hash);
    }
}
