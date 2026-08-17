<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

final class ApiController extends Controller
{
    public function index(): void
    {
        $this->json([
            'product' => 'BGV Enterprise',
            'version' => '1.0 Foundation',
            'docs' => url('/api/v1/docs'),
        ]);
    }

    public function openApi(): void
    {
        $this->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'BGV Enterprise API',
                'version' => '1.0.0-foundation',
                'description' => 'API base de BGV Enterprise',
            ],
            'paths' => [
                '/api/v1/health' => ['get' => ['summary' => 'Estado del servicio']],
                '/api/v1/clients' => [
                    'get' => ['summary' => 'Listar clientes'],
                    'post' => ['summary' => 'Crear cliente'],
                ],
                '/api/v1/clients/{client_id}' => [
                    'get' => ['summary' => 'Consultar cliente'],
                ],
            ],
        ]);
    }

    public function docs(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $openApiUrl = e(url('/api/v1/openapi.json'));
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>BGV Enterprise API</title><style>body{font-family:Arial,sans-serif;max-width:920px;margin:40px auto;padding:0 20px;color:#1f2937}h1,code{color:#17365d}section{border:1px solid #dce3ea;border-radius:10px;padding:18px;margin:14px 0}code{background:#eef4f8;padding:3px 6px;border-radius:4px}</style></head><body>'
            . '<h1>BGV Enterprise API</h1><p>Versión 1.0.0-foundation</p>'
            . '<section><strong>GET</strong> <code>/api/v1/health</code><p>Estado del servicio.</p></section>'
            . '<section><strong>GET · POST</strong> <code>/api/v1/clients</code><p>Listar o crear clientes.</p></section>'
            . '<section><strong>GET</strong> <code>/api/v1/clients/{client_id}</code><p>Consultar un cliente.</p></section>'
            . '<p><a href="' . $openApiUrl . '">Ver especificación OpenAPI JSON</a></p></body></html>';
    }
}
