<?php

declare(strict_types=1);

namespace App\Core;

final class Cors
{
    public static function handle(): void
    {
        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        $allowed = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) Env::get('CORS_ORIGINS', 'http://localhost:5173'))
        )));

        if ($origin !== '' && (in_array('*', $allowed, true) || in_array($origin, $allowed, true))) {
            header('Access-Control-Allow-Origin: ' . (in_array('*', $allowed, true) ? '*' : $origin));
            header('Vary: Origin');
            if (!in_array('*', $allowed, true)) {
                header('Access-Control-Allow-Credentials: true');
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 600');

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
