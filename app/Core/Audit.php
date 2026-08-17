<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;

final class Audit
{
    public static function log(string $action, string $module, ?string $recordId = null, ?array $before = null, ?array $after = null): void
    {
        try {
            $statement = Database::connection()->prepare(
                'INSERT INTO audit_logs (id, company_id, user_id, module, record_id, action, previous_data, new_data, ip_address)
                 VALUES (:id, :company_id, :user_id, :module, :record_id, :action, :previous_data, :new_data, :ip_address)'
            );
            $statement->execute([
                'id' => Id::uuid(), 'company_id' => Auth::companyId(), 'user_id' => Auth::id(),
                'module' => $module, 'record_id' => $recordId, 'action' => $action,
                'previous_data' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE),
                'new_data' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
                'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ]);
        } catch (PDOException) {
            // La auditoría no debe interrumpir la operación si la migración aún no se aplicó.
        }
    }
}
