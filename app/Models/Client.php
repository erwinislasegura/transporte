<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

final class Client
{
    public static function all(): array
    {
        $sql = 'SELECT id, company_id, code, rut, business_name, payment_condition,
                       requires_oc, requires_hes, active, created_at
                FROM clients ORDER BY business_name';

        return Connection::connection()->query($sql)->fetchAll();
    }

    public static function find(string $id): ?array
    {
        $statement = Connection::connection()->prepare(
            'SELECT id, company_id, code, rut, business_name, payment_condition,
                    requires_oc, requires_hes, active, created_at
             FROM clients WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $client = $statement->fetch();

        return $client === false ? null : $client;
    }

    public static function create(array $data): array
    {
        $id = self::uuid();
        $statement = Connection::connection()->prepare(
            'INSERT INTO clients
                (id, company_id, code, rut, business_name, payment_condition, requires_oc, requires_hes, active)
             VALUES
                (:id, :company_id, :code, :rut, :business_name, :payment_condition, :requires_oc, :requires_hes, 1)'
        );
        $statement->execute([
            'id' => $id,
            'company_id' => $data['company_id'],
            'code' => $data['code'],
            'rut' => $data['rut'],
            'business_name' => $data['business_name'],
            'payment_condition' => $data['payment_condition'],
            'requires_oc' => $data['requires_oc'],
            'requires_hes' => $data['requires_hes'],
        ]);

        return self::find($id) ?? [];
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}
