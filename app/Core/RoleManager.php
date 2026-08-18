<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Connection;
use PDOException;

final class RoleManager
{
    public static function all(bool $activeOnly = false): array
    {
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
    }

    public static function find(string $id): ?array
    {
        try {
            $statement = Connection::connection()->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $role = $statement->fetch();
            if ($role === false) return null;
            $role['permissions'] = self::decode((string) $role['permissions_json']);
            return $role;
        } catch (PDOException) {
            return null;
        }
    }

    public static function permissions(string $roleName): array
    {
        try {
            $statement = Connection::connection()->prepare('SELECT permissions_json FROM roles WHERE name = :name AND active = 1 LIMIT 1');
            $statement->execute(['name' => $roleName]);
            $value = $statement->fetchColumn();
            if ($value !== false) return self::decode((string) $value);
        } catch (PDOException) {
            // Mantiene compatibilidad hasta ejecutar la migración de roles.
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
        $role = self::find($id);
        if ($role === null) return;
        $statement = Connection::connection()->prepare(
            'UPDATE roles SET name = :name, description = :description, permissions_json = :permissions, active = :active WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
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
