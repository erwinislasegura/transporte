<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;

final class Company
{
    public static function active(): array
    {
        return Connection::connection()
            ->query('SELECT id, rut, legal_name, trade_name FROM companies WHERE active = 1 ORDER BY legal_name')
            ->fetchAll();
    }
}
