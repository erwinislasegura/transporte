<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Connection;
use PDO;
use PDOException;

final class RecordRepository
{
    public static function all(array $module, ?string $companyId, string $search = ''): array
    {
        $table = self::identifier($module['table']);
        $where = [];
        $params = [];
        if ($module['companyScoped'] && $companyId !== null) {
            $where[] = 'company_id = :company_id';
            $params['company_id'] = $companyId;
        }
        if ($search !== '') {
            $searchable = array_unique(array_merge(array_keys($module['columns']), array_keys($module['fields'])));
            $parts = [];
            foreach ($searchable as $index => $column) {
                $column = self::identifier($column);
                if (($module['fields'][$column]['virtual'] ?? false) === true) continue;
                $key = 'search_' . $index;
                $parts[] = "CAST({$column} AS CHAR) LIKE :{$key}";
                $params[$key] = '%' . $search . '%';
            }
            if ($parts !== []) $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        $sql = "SELECT * FROM {$table}" . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where)) . ' ORDER BY created_at DESC LIMIT 250';
        $statement = Connection::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function find(array $module, string $id, ?string $companyId): ?array
    {
        $table = self::identifier($module['table']);
        $sql = "SELECT * FROM {$table} WHERE id = :id";
        $params = ['id' => $id];
        if ($module['companyScoped'] && $companyId !== null) {
            $sql .= ' AND company_id = :company_id';
            $params['company_id'] = $companyId;
        }
        $sql .= ' LIMIT 1';
        $statement = Connection::connection()->prepare($sql);
        $statement->execute($params);
        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    public static function create(array $module, array $data, ?string $companyId): array
    {
        $table = self::identifier($module['table']);
        $id = Id::uuid();
        if (($module['prefix'] ?? null) !== null && array_key_exists('code', $module['fields']) && empty($data['code'])) {
            $data['code'] = self::nextCode($module, $companyId);
        }
        $columns = ['id'];
        $values = ['id' => $id];
        if ($module['companyScoped'] && $companyId !== null) {
            $columns[] = 'company_id';
            $values['company_id'] = $companyId;
        }
        foreach ($module['fields'] as $column => $field) {
            if (($field['virtual'] ?? false) || !array_key_exists($column, $data)) continue;
            $columns[] = self::identifier($column);
            $values[$column] = $data[$column];
        }
        if ($table === 'users' && isset($data['password_hash'])) {
            $columns[] = 'password_hash';
            $values['password_hash'] = $data['password_hash'];
        }

        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = Connection::connection()->prepare($sql);
        $statement->execute($values);
        $record = self::find($module, $id, $companyId) ?? [];
        Audit::log('record.created', $table, $id, null, $record);

        return $record;
    }

    public static function update(array $module, string $id, array $data, ?string $companyId): array
    {
        $table = self::identifier($module['table']);
        $before = self::find($module, $id, $companyId);
        if ($before === null) return [];
        $sets = [];
        $params = ['id' => $id];
        foreach ($module['fields'] as $column => $field) {
            if (($field['virtual'] ?? false) || !array_key_exists($column, $data)) continue;
            $column = self::identifier($column);
            $sets[] = "{$column} = :{$column}";
            $params[$column] = $data[$column];
        }
        if ($table === 'users' && isset($data['password_hash'])) {
            $sets[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }
        if ($sets === []) return $before;
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . ', updated_at = UTC_TIMESTAMP() WHERE id = :id';
        if ($module['companyScoped'] && $companyId !== null) {
            $sql .= ' AND company_id = :company_id';
            $params['company_id'] = $companyId;
        }
        $statement = Connection::connection()->prepare($sql);
        $statement->execute($params);
        $after = self::find($module, $id, $companyId) ?? [];
        Audit::log('record.updated', $table, $id, $before, $after);

        return $after;
    }

    public static function archive(array $module, string $id, ?string $companyId): void
    {
        $table = self::identifier($module['table']);
        $before = self::find($module, $id, $companyId);
        if ($before === null) return;
        $params = ['id' => $id];
        if (isset($module['fields']['status'])) {
            $options = array_keys($module['fields']['status']['options'] ?? []);
            $status = in_array('Anulada', $options, true) ? 'Anulada' : (in_array('Cerrada', $options, true) ? 'Cerrada' : end($options));
            $sql = "UPDATE {$table} SET status = :status, updated_at = UTC_TIMESTAMP() WHERE id = :id";
            $params['status'] = $status ?: 'Inactivo';
        } elseif (isset($module['fields']['active'])) {
            $sql = "UPDATE {$table} SET active = 0, updated_at = UTC_TIMESTAMP() WHERE id = :id";
        } else {
            return;
        }
        if ($module['companyScoped'] && $companyId !== null) {
            $sql .= ' AND company_id = :company_id';
            $params['company_id'] = $companyId;
        }
        Connection::connection()->prepare($sql)->execute($params);
        Audit::log('record.archived', $table, $id, $before, self::find($module, $id, $companyId));
    }

    public static function relationOptions(array $relation, ?string $companyId): array
    {
        $table = self::identifier($relation['table']);
        $value = self::identifier($relation['value']);
        $label = self::identifier($relation['label']);
        try {
            $statement = Connection::connection()->prepare("SELECT {$value}, {$label} FROM {$table} WHERE company_id = :company_id ORDER BY {$label} LIMIT 500");
            $statement->execute(['company_id' => $companyId]);
        } catch (PDOException) {
            $statement = Connection::connection()->query("SELECT {$value}, {$label} FROM {$table} ORDER BY {$label} LIMIT 500");
        }

        return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function count(string $table, ?string $companyId, string $where = '1=1'): int
    {
        $table = self::identifier($table);
        $params = [];
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        if ($companyId !== null) {
            $sql .= ' AND company_id = :company_id';
            $params['company_id'] = $companyId;
        }
        try {
            $statement = Connection::connection()->prepare($sql);
            $statement->execute($params);
            return (int) $statement->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    private static function nextCode(array $module, ?string $companyId): string
    {
        $table = self::identifier($module['table']);
        $params = [];
        $sql = "SELECT COUNT(*) + 1 FROM {$table}";
        if ($module['companyScoped'] && $companyId !== null) {
            $sql .= ' WHERE company_id = :company_id';
            $params['company_id'] = $companyId;
        }
        $statement = Connection::connection()->prepare($sql);
        $statement->execute($params);
        $sequence = (int) $statement->fetchColumn();
        $prefix = (string) $module['prefix'];
        if ($prefix === 'JOR') return sprintf('JOR-%s-%06d', date('Ymd'), $sequence);
        if (in_array($prefix, ['COT','OCC','VIA','GUI','LIQ','FAC','OT','INC'], true)) {
            return sprintf('%s-%s-%06d', $prefix, date('Y'), $sequence);
        }
        return sprintf('%s-%06d', $prefix, $sequence);
    }

    private static function identifier(string $value): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $value)) {
            throw new \InvalidArgumentException('Identificador SQL no permitido.');
        }
        return $value;
    }
}
