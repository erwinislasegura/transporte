<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Company
{
    public static function active(): array
    {
        return Database::connection()
            ->query('SELECT id, rut, legal_name, trade_name FROM companies WHERE active = 1 ORDER BY legal_name')
            ->fetchAll();
    }
}
