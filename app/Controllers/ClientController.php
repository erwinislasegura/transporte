<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Client;
use App\Models\Company;
use PDOException;

final class ClientController extends Controller
{
    public function index(): void
    {
        try {
            $clients = Client::all();
            $databaseError = null;
        } catch (PDOException) {
            $clients = [];
            $databaseError = 'No fue posible cargar los clientes.';
        }

        $this->view('clients/index', [
            'title' => 'Clientes',
            'activeMenu' => 'clients',
            'clients' => $clients,
            'databaseError' => $databaseError,
        ]);
    }

    public function create(): void
    {
        try {
            $companies = Company::active();
            $databaseError = null;
        } catch (PDOException) {
            $companies = [];
            $databaseError = 'No fue posible cargar las empresas.';
        }

        $this->view('clients/create', [
            'title' => 'Nuevo cliente',
            'activeMenu' => 'clients',
            'companies' => $companies,
            'databaseError' => $databaseError,
        ]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('La sesión del formulario expiró. Recargue la página e inténtelo nuevamente.');
        }

        $data = $this->input($_POST);
        $errors = $this->validate($data);

        if ($errors !== []) {
            $_SESSION['_flash']['errors'] = $errors;
            $_SESSION['_old'] = $data;
            $this->redirect('/clients/create');
        }

        try {
            $client = Client::create($data);
            $_SESSION['_flash']['success'] = 'Cliente creado correctamente.';
            $this->redirect('/clients/' . $client['id']);
        } catch (PDOException $exception) {
            $_SESSION['_flash']['errors'] = [
                $exception->getCode() === '23000'
                    ? 'La empresa seleccionada no existe o no está disponible.'
                    : 'No fue posible guardar el cliente.',
            ];
            $_SESSION['_old'] = $data;
            $this->redirect('/clients/create');
        }
    }

    public function show(string $id): void
    {
        $client = Client::find($id);
        if ($client === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Cliente no encontrado', 'activeMenu' => 'clients']);
            return;
        }

        $this->view('clients/show', [
            'title' => $client['business_name'],
            'activeMenu' => 'clients',
            'client' => $client,
        ]);
    }

    private function input(array $source): array
    {
        $keys = ['company_id', 'code', 'rut', 'business_name', 'payment_condition', 'requires_oc', 'requires_hes'];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = trim((string) ($source[$key] ?? ''));
        }

        return $data;
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['company_id'] === '') $errors[] = 'Seleccione una empresa.';
        if (strlen($data['code']) < 3 || strlen($data['code']) > 30) $errors[] = 'El código debe tener entre 3 y 30 caracteres.';
        if (strlen($data['rut']) < 8 || strlen($data['rut']) > 12) $errors[] = 'Ingrese un RUT válido.';
        if (strlen($data['business_name']) < 2 || strlen($data['business_name']) > 180) $errors[] = 'Ingrese una razón social válida.';
        if ($data['payment_condition'] === '') $errors[] = 'Ingrese la condición de pago.';
        if (!in_array($data['requires_oc'], ['Opcional', 'Sí', 'No'], true)) $errors[] = 'El valor de OC no es válido.';
        if (!in_array($data['requires_hes'], ['Opcional', 'Sí', 'No'], true)) $errors[] = 'El valor de HES no es válido.';

        return $errors;
    }
}
