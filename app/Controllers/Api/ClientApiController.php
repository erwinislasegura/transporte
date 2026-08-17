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
            $this->json(array_map([$this, 'serialize'], Client::all()));
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

        $data = [
            'company_id' => trim((string) ($payload['company_id'] ?? '')),
            'code' => trim((string) ($payload['code'] ?? '')),
            'rut' => trim((string) ($payload['rut'] ?? '')),
            'business_name' => trim((string) ($payload['business_name'] ?? '')),
            'payment_condition' => trim((string) ($payload['payment_condition'] ?? 'Transferencia 30 días')),
            'requires_oc' => trim((string) ($payload['requires_oc'] ?? 'Opcional')),
            'requires_hes' => trim((string) ($payload['requires_hes'] ?? 'Opcional')),
        ];

        $errors = $this->validate($data);
        if ($errors !== []) {
            $this->json(['detail' => $errors], 422);
        }

        try {
            $this->json($this->serialize(Client::create($data)), 201);
        } catch (PDOException $exception) {
            $status = $exception->getCode() === '23000' ? 422 : 500;
            $detail = $status === 422 ? 'La empresa indicada no existe.' : 'No fue posible crear el cliente.';
            $this->json(['detail' => $detail], $status);
        }
    }

    public function show(string $id): void
    {
        $client = Client::find($id);
        if ($client === null) {
            $this->json(['detail' => 'Cliente no encontrado'], 404);
        }

        $this->json($this->serialize($client));
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $data['company_id'])) {
            $errors[] = ['field' => 'company_id', 'message' => 'Debe ser un UUID válido.'];
        }
        if (strlen($data['code']) < 3 || strlen($data['code']) > 30) {
            $errors[] = ['field' => 'code', 'message' => 'Debe tener entre 3 y 30 caracteres.'];
        }
        if (strlen($data['rut']) < 8 || strlen($data['rut']) > 12) {
            $errors[] = ['field' => 'rut', 'message' => 'Debe tener entre 8 y 12 caracteres.'];
        }
        if (strlen($data['business_name']) < 2 || strlen($data['business_name']) > 180) {
            $errors[] = ['field' => 'business_name', 'message' => 'Debe tener entre 2 y 180 caracteres.'];
        }
        if ($data['payment_condition'] === '' || strlen($data['payment_condition']) > 80) {
            $errors[] = ['field' => 'payment_condition', 'message' => 'La condición de pago no es válida.'];
        }
        foreach (['requires_oc', 'requires_hes'] as $field) {
            if ($data[$field] === '' || strlen($data[$field]) > 20) {
                $errors[] = ['field' => $field, 'message' => 'El valor no es válido.'];
            }
        }

        return $errors;
    }

    private function serialize(array $client): array
    {
        return [
            'id' => (string) $client['id'],
            'company_id' => (string) $client['company_id'],
            'code' => (string) $client['code'],
            'rut' => (string) $client['rut'],
            'business_name' => (string) $client['business_name'],
            'payment_condition' => (string) $client['payment_condition'],
            'requires_oc' => (string) $client['requires_oc'],
            'requires_hes' => (string) $client['requires_hes'],
            'active' => (bool) $client['active'],
        ];
    }
}
