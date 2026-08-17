<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Client;
use PDOException;

final class ClientApiController extends Controller
{
    public function index(): void
    {
        try {
            $this->json(Client::all());
        } catch (PDOException) {
            $this->json(['detail' => 'No fue posible cargar los clientes.'], 500);
        }
    }

    public function store(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $required = ['company_id', 'code', 'rut', 'business_name'];
        foreach ($required as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $this->json(['detail' => "El campo {$field} es obligatorio."], 422);
            }
        }

        $data = [
            'company_id' => trim((string) $payload['company_id']),
            'code' => trim((string) $payload['code']),
            'rut' => trim((string) $payload['rut']),
            'business_name' => trim((string) $payload['business_name']),
            'payment_condition' => trim((string) ($payload['payment_condition'] ?? 'Transferencia 30 días')),
            'requires_oc' => trim((string) ($payload['requires_oc'] ?? 'Opcional')),
            'requires_hes' => trim((string) ($payload['requires_hes'] ?? 'Opcional')),
        ];

        try {
            $this->json(Client::create($data), 201);
        } catch (PDOException $exception) {
            $status = $exception->getCode() === '23000' ? 409 : 500;
            $detail = $status === 409 ? 'El cliente ya existe.' : 'No fue posible crear el cliente.';
            $this->json(['detail' => $detail], $status);
        }
    }

    public function show(string $id): void
    {
        $client = Client::find($id);
        if ($client === null) {
            $this->json(['detail' => 'Cliente no encontrado'], 404);
        }

        $this->json($client);
    }
}
