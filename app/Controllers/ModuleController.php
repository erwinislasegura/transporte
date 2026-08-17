<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Database\Connection;
use App\Core\Id;
use App\Core\RecordRepository;
use App\Core\Security;
use PDOException;

final class ModuleController extends Controller
{
    public function index(string $slug): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area']);
        $search = trim((string) ($_GET['q'] ?? ''));
        try {
            $records = RecordRepository::all($module, Auth::companyId(), $search);
            $error = null;
        } catch (PDOException) {
            $records = [];
            $error = 'Este módulo requiere aplicar la actualización de base de datos del Documento Maestro.';
        }
        [$records, $relationLabels] = $this->resolveRelations($module, $records);
        $this->view('modules/index', [
            'title' => $module['title'], 'activeMenu' => $slug, 'moduleSlug' => $slug, 'module' => $module,
            'records' => $records, 'relationLabels' => $relationLabels, 'search' => $search, 'databaseError' => $error,
            'canManage' => Auth::can($module['area'], 'manage'),
        ]);
    }

    public function create(string $slug): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area'], 'manage');
        $this->view('modules/form', [
            'title' => 'Nuevo · ' . $module['singular'], 'activeMenu' => $slug, 'moduleSlug' => $slug,
            'module' => $module, 'record' => [], 'options' => $this->relationOptions($module), 'mode' => 'create',
        ]);
    }

    public function store(string $slug): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area'], 'manage');
        $this->checkCsrf($slug);
        $data = $this->collect($module, true);
        $errors = $this->validate($module, $data, true);
        if ($errors !== []) $this->formError($slug, $errors, $data);
        try {
            $record = RecordRepository::create($module, $data, Auth::companyId());
            $_SESSION['_flash']['success'] = $module['singular'] . ' creado correctamente.';
            $this->redirect('/modules/' . $slug . '/' . $record['id']);
        } catch (PDOException $exception) {
            $this->formError($slug, [$this->databaseMessage($exception)], $data);
        }
    }

    public function show(string $slug, string $id): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area']);
        $record = RecordRepository::find($module, $id, Auth::companyId());
        if ($record === null) $this->notFound();
        [$records, $relationLabels] = $this->resolveRelations($module, [$record]);
        $this->view('modules/show', [
            'title' => $module['singular'], 'activeMenu' => $slug, 'moduleSlug' => $slug, 'module' => $module,
            'record' => $records[0], 'relationLabels' => $relationLabels, 'canManage' => Auth::can($module['area'], 'manage'),
        ]);
    }

    public function edit(string $slug, string $id): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area'], 'manage');
        $record = RecordRepository::find($module, $id, Auth::companyId());
        if ($record === null) $this->notFound();
        $this->view('modules/form', [
            'title' => 'Editar · ' . $module['singular'], 'activeMenu' => $slug, 'moduleSlug' => $slug,
            'module' => $module, 'record' => $record, 'options' => $this->relationOptions($module), 'mode' => 'edit',
        ]);
    }

    public function update(string $slug, string $id): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area'], 'manage');
        $this->checkCsrf($slug);
        $data = $this->collect($module, false);
        $errors = $this->validate($module, $data, false);
        if ($errors !== []) {
            $_SESSION['_flash']['errors'] = $errors;
            $_SESSION['_old'] = $data;
            $this->redirect('/modules/' . $slug . '/' . $id . '/edit');
        }
        try {
            RecordRepository::update($module, $id, $data, Auth::companyId());
            $_SESSION['_flash']['success'] = 'Cambios guardados con trazabilidad.';
            $this->redirect('/modules/' . $slug . '/' . $id);
        } catch (PDOException $exception) {
            $_SESSION['_flash']['errors'] = [$this->databaseMessage($exception)];
            $_SESSION['_old'] = $data;
            $this->redirect('/modules/' . $slug . '/' . $id . '/edit');
        }
    }

    public function archive(string $slug, string $id): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area'], 'manage');
        $this->checkCsrf($slug);
        RecordRepository::archive($module, $id, Auth::companyId());
        $_SESSION['_flash']['success'] = 'El registro fue cerrado o desactivado sin perder trazabilidad.';
        $this->redirect('/modules/' . $slug);
    }

    public function download(string $slug, string $id): void
    {
        $module = $this->module($slug);
        Auth::requirePermission($module['area']);
        $record = RecordRepository::find($module, $id, Auth::companyId());
        $relative = (string) ($record['file_path'] ?? '');
        $root = realpath(BASE_PATH . '/storage/uploads');
        $file = realpath(BASE_PATH . '/' . ltrim($relative, '/'));
        if ($root === false || $file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file)) $this->notFound();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    private function module(string $slug): array
    {
        $modules = require BASE_PATH . '/config/modules.php';
        if (!isset($modules[$slug])) $this->notFound();
        return $modules[$slug];
    }

    private function collect(array $module, bool $creating): array
    {
        $data = [];
        foreach ($module['fields'] as $column => $field) {
            $type = $field['type'] ?? 'text';
            if ($type === 'file') {
                $uploaded = $this->upload($column);
                if ($uploaded !== null) $data[$column] = $uploaded;
                continue;
            }
            if ($type === 'checkbox') {
                $data[$column] = isset($_POST[$column]) ? 1 : 0;
                continue;
            }
            $value = trim((string) ($_POST[$column] ?? ''));
            if ($type === 'datetime-local' && $value !== '') $value = str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
            if (($field['virtual'] ?? false) && $column === 'password') {
                if ($value !== '') $data['password_hash'] = Security::hashPassword($value);
                continue;
            }
            $data[$column] = $value === '' && !$creating ? null : ($value === '' ? ($field['default'] ?? null) : $value);
        }
        $this->calculate($module['table'], $data);
        return $data;
    }

    private function calculate(string $table, array &$data): void
    {
        if (in_array($table, ['quotations','supplier_purchase_orders','customer_invoices'], true)) {
            $net = (float) ($data['net_amount'] ?? 0);
            if ((float) ($data['tax_amount'] ?? 0) === 0.0) $data['tax_amount'] = round($net * 0.19, 2);
            if ((float) ($data['total'] ?? 0) === 0.0) $data['total'] = round($net + (float) $data['tax_amount'], 2);
            if ($table === 'customer_invoices' && (float) ($data['balance'] ?? 0) === 0.0) $data['balance'] = $data['total'];
        }
        if ($table === 'payment_statements') {
            $data['balance'] = max(0, (float) ($data['total'] ?? 0) - (float) ($data['paid_amount'] ?? 0) - (float) ($data['retention_amount'] ?? 0));
        }
        if ($table === 'fuel_records' && (float) ($data['total_cost'] ?? 0) === 0.0) {
            $data['total_cost'] = round((float) ($data['liters'] ?? 0) * (float) ($data['unit_cost'] ?? 0), 2);
        }
        if ($table === 'trip_events' && !empty($data['started_at']) && !empty($data['ended_at'])) {
            $data['duration_minutes'] = max(0, (int) round((strtotime($data['ended_at']) - strtotime($data['started_at'])) / 60));
        }
    }

    private function validate(array $module, array $data, bool $creating): array
    {
        $errors = [];
        foreach ($module['fields'] as $column => $field) {
            $required = ($field['required'] ?? false) || ($creating && ($field['required_on_create'] ?? false));
            $key = ($field['virtual'] ?? false) && $column === 'password' ? 'password_hash' : $column;
            if ($required && (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '')) {
                $errors[] = 'El campo “' . $field['label'] . '” es obligatorio.';
            }
            if (($field['type'] ?? '') === 'email' && !empty($data[$column]) && !filter_var($data[$column], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El correo ingresado no es válido.';
            }
        }
        $datePairs = [
            ['start_date','end_date'], ['valid_from','valid_until'], ['issue_date','due_date'],
            ['issued_at','expires_at'], ['hire_date','contract_end_date'], ['start_at','end_at'],
            ['started_at','ended_at'],
        ];
        foreach ($datePairs as [$start, $end]) {
            if (!empty($data[$start]) && !empty($data[$end]) && strtotime((string) $data[$end]) < strtotime((string) $data[$start])) {
                $errors[] = 'La fecha de término o vencimiento no puede ser anterior a la fecha de inicio.';
                break;
            }
        }
        if ($module['table'] === 'workdays' && in_array($data['status'] ?? '', ['Cerrada','Aprobada'], true) && empty($data['end_at'])) {
            $errors[] = 'Una jornada cerrada o aprobada debe registrar su hora de término.';
        }
        if ($module['table'] === 'work_orders' && in_array($data['status'] ?? '', ['Terminada','Cerrada'], true) && empty($data['diagnosis'])) {
            $errors[] = 'Una orden terminada o cerrada debe incluir el diagnóstico técnico.';
        }
        if ($module['table'] === 'customer_invoices' && !empty($data['client_id'])) {
            try {
                $statement = Connection::connection()->prepare('SELECT requires_oc, requires_hes FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1');
                $statement->execute(['id' => $data['client_id'], 'company_id' => Auth::companyId()]);
                $client = $statement->fetch();
                if (($client['requires_oc'] ?? '') === 'Sí' && empty($data['client_order_id'])) $errors[] = 'Este cliente exige una orden de compra antes de facturar.';
                if (($client['requires_hes'] ?? '') === 'Sí' && empty($data['hes_id'])) $errors[] = 'Este cliente exige una HES antes de facturar.';
            } catch (PDOException) {
                $errors[] = 'No fue posible validar las condiciones comerciales del cliente.';
            }
        }
        return $errors;
    }

    private function relationOptions(array $module): array
    {
        $options = [];
        foreach ($module['fields'] as $column => $field) {
            if (isset($field['relation'])) $options[$column] = RecordRepository::relationOptions($field['relation'], Auth::companyId());
        }
        return $options;
    }

    private function resolveRelations(array $module, array $records): array
    {
        $labels = [];
        foreach ($module['fields'] as $column => $field) {
            if (!isset($field['relation'])) continue;
            try { $labels[$column] = RecordRepository::relationOptions($field['relation'], Auth::companyId()); }
            catch (PDOException) { $labels[$column] = []; }
        }
        return [$records, $labels];
    }

    private function upload(string $field): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) $file['size'] > 15 * 1024 * 1024) return null;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/msword'=>'doc','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx'];
        if (!isset($allowed[$mime])) return null;
        $directory = BASE_PATH . '/storage/uploads';
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        $name = Id::uuid() . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) return null;
        return 'storage/uploads/' . $name;
    }

    private function checkCsrf(string $slug): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['_flash']['error'] = 'La sesión expiró. Vuelve a intentarlo.';
            $this->redirect('/modules/' . $slug);
        }
    }

    private function formError(string $slug, array $errors, array $data): never
    {
        $_SESSION['_flash']['errors'] = $errors;
        $_SESSION['_old'] = $data;
        $this->redirect('/modules/' . $slug . '/create');
    }

    private function databaseMessage(PDOException $exception): string
    {
        return $exception->getCode() === '23000'
            ? 'Existe un registro duplicado o una relación seleccionada no es válida.'
            : 'No fue posible guardar. Verifica la actualización MySQL del Documento Maestro.';
    }

    private function notFound(): never
    {
        http_response_code(404);
        $this->view('errors/404', ['title' => 'Registro no encontrado', 'activeMenu' => '']);
        exit;
    }
}
