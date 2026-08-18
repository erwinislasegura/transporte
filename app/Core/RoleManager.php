<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Connection;
use PDOException;

final class RoleManager
{
    private static bool $storageChecked = false;

    public static function all(bool $activeOnly = false): array
    {
        self::ensureStorage();
        try {
            $sql = 'SELECT id, name, description, permissions_json, protected, active FROM roles';
            if ($activeOnly) $sql .= ' WHERE active = 1';
            $sql .= ' ORDER BY protected DESC, name ASC';
            $rows = Connection::connection()->query($sql)->fetchAll();
            foreach ($rows as &$row) {
                $row['permissions'] = self::decode((string) ($row['permissions_json'] ?? '[]'));
            }
            unset($row);
            return $rows;
        } catch (PDOException) {
            return self::fallbackRows();
        }
    }

    public static function find(string $id): ?array
    {
        self::ensureStorage();
        try {
            $statement = Connection::connection()->prepare('SELECT * FROM roles WHERE id = :id OR name = :name LIMIT 1');
            $statement->execute(['id' => $id, 'name' => rawurldecode($id)]);
            $role = $statement->fetch();
            if ($role !== false) {
                $role['permissions'] = self::decode((string) $role['permissions_json']);
                return $role;
            }
        } catch (PDOException) {
            // Usa configuración local como respaldo si el esquema no está disponible.
        }

        foreach (self::fallbackRows() as $role) {
            if ((string) $role['id'] === $id || (string) $role['name'] === rawurldecode($id)) {
                return $role;
            }
        }
        return null;
    }

    public static function permissions(string $roleName): array
    {
        self::ensureStorage();
        try {
            $statement = Connection::connection()->prepare('SELECT permissions_json FROM roles WHERE name = :name AND active = 1 LIMIT 1');
            $statement->execute(['name' => $roleName]);
            $value = $statement->fetchColumn();
            if ($value !== false) return self::decode((string) $value);
        } catch (PDOException) {
            // Mantiene compatibilidad si MySQL todavía no permite crear la tabla.
        }
        $fallback = require BASE_PATH . '/config/roles.php';
        return $fallback[$roleName] ?? [];
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::all(true) as $role) $options[(string) $role['name']] = (string) $role['name'];
        return $options;
    }

    public static function permissionCatalog(): array
    {
        $modules = require BASE_PATH . '/config/modules.php';
        $catalog = [
            'dashboard' => ['label' => 'Centro de Control', 'group' => 'General'],
            'bi' => ['label' => 'Reportes ejecutivos', 'group' => 'General'],
        ];
        foreach ($modules as $module) {
            $area = (string) $module['area'];
            if (!isset($catalog[$area])) {
                $catalog[$area] = ['label' => self::areaLabel($area), 'group' => (string) $module['group']];
            }
        }
        ksort($catalog);
        return $catalog;
    }

    public static function create(string $name, string $description, array $permissions): string
    {
        self::ensureStorage();
        $id = Id::uuid();
        $statement = Connection::connection()->prepare(
            'INSERT INTO roles (id, name, description, permissions_json, protected, active) VALUES (:id, :name, :description, :permissions, 0, 1)'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'permissions' => json_encode(array_values(array_unique($permissions)), JSON_UNESCAPED_UNICODE),
        ]);
        return $id;
    }

    public static function update(string $id, string $name, string $description, array $permissions, bool $active): void
    {
        self::ensureStorage();
        $role = self::find($id);
        if ($role === null) return;

        // Si llegó un rol de respaldo, intenta resolver su ID real luego de inicializar MySQL.
        $realId = (string) ($role['id'] ?? $id);
        try {
            $lookup = Connection::connection()->prepare('SELECT id FROM roles WHERE id = :id OR name = :name LIMIT 1');
            $lookup->execute(['id' => $realId, 'name' => (string) $role['name']]);
            $resolved = $lookup->fetchColumn();
            if ($resolved !== false) $realId = (string) $resolved;
        } catch (PDOException) {
            // La escritura posterior entregará el error correspondiente.
        }

        $statement = Connection::connection()->prepare(
            'UPDATE roles SET name = :name, description = :description, permissions_json = :permissions, active = :active WHERE id = :id'
        );
        $statement->execute([
            'id' => $realId,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'permissions' => json_encode(array_values(array_unique($permissions)), JSON_UNESCAPED_UNICODE),
            'active' => $active ? 1 : 0,
        ]);
        if ((string) $role['name'] !== $name) {
            $users = Connection::connection()->prepare('UPDATE users SET role = :new_name WHERE role = :old_name');
            $users->execute(['new_name' => $name, 'old_name' => $role['name']]);
        }
    }

    private static function ensureStorage(): void
    {
        if (self::$storageChecked) return;
        self::$storageChecked = true;

        try {
            $db = Connection::connection();
            $db->exec(
                'CREATE TABLE IF NOT EXISTS roles (
                    id CHAR(36) NOT NULL PRIMARY KEY,
                    name VARCHAR(80) NOT NULL UNIQUE,
                    description VARCHAR(255) NULL,
                    permissions_json LONGTEXT NOT NULL,
                    protected TINYINT(1) NOT NULL DEFAULT 0,
                    active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            $existing = $db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
            $insert = $db->prepare(
                'INSERT INTO roles (id, name, description, permissions_json, protected, active)
                 VALUES (:id, :name, :description, :permissions, :protected, 1)'
            );
            foreach (self::fallbackRows() as $role) {
                $existing->execute(['name' => $role['name']]);
                if ($existing->fetchColumn() !== false) continue;
                $insert->execute([
                    'id' => Id::uuid(),
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'permissions' => json_encode($role['permissions'], JSON_UNESCAPED_UNICODE),
                    'protected' => (int) $role['protected'],
                ]);
            }
        } catch (PDOException) {
            // El módulo seguirá funcionando en modo lectura con config/roles.php.
        }
    }

    private static function fallbackRows(): array
    {
        $fallback = require BASE_PATH . '/config/roles.php';
        $rows = [];
        foreach ($fallback as $name => $permissions) {
            $rows[] = [
                'id' => $name,
                'name' => $name,
                'description' => 'Rol definido en configuración local.',
                'permissions_json' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
                'permissions' => $permissions,
                'protected' => in_array($name, ['Super Administrador', 'Maestro'], true) ? 1 : 0,
                'active' => 1,
            ];
        }
        return $rows;
    }

    private static function decode(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    private static function areaLabel(string $area): string
    {
        return match ($area) {
            'security' => 'Seguridad y usuarios',
            'commercial' => 'Comercial',
            'operations' => 'Operaciones',
            'fleet' => 'Flota',
            'workshop' => 'Taller',
            'fuel' => 'Combustible',
            'purchasing' => 'Compras',
            'billing' => 'Facturación',
            'finance' => 'Finanzas',
            'hr' => 'Recursos Humanos',
            'hse' => 'Prevención / HSE',
            'environment' => 'Medio Ambiente',
            'documents' => 'Documentos',
            'approvals' => 'Aprobaciones',
            default => ucfirst(str_replace('-', ' ', $area)),
        };
    }
}
