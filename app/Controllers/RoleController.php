<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\RoleManager;
use PDOException;

final class RoleController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('security', 'manage');
        $this->view('roles/index', [
            'title' => 'Roles y permisos',
            'activeMenu' => 'roles',
            'roles' => RoleManager::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('security', 'manage');
        $this->view('roles/form', [
            'title' => 'Nuevo rol',
            'activeMenu' => 'roles',
            'role' => ['name' => '', 'description' => '', 'permissions' => [], 'active' => 1, 'protected' => 0],
            'catalog' => RoleManager::permissionCatalog(),
            'mode' => 'create',
        ]);
    }

    public function store(): void
    {
        Auth::requirePermission('security', 'manage');
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['_flash']['error'] = 'La sesión del formulario expiró.';
            $this->redirect('/roles/create');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $permissions = $this->permissionsFromRequest();
        if ($name === '' || mb_strlen($name) < 3) {
            $_SESSION['_flash']['error'] = 'El nombre del rol debe tener al menos 3 caracteres.';
            $_SESSION['_old'] = $_POST;
            $this->redirect('/roles/create');
        }
        if ($permissions === []) {
            $_SESSION['_flash']['error'] = 'Selecciona al menos un permiso para el rol.';
            $_SESSION['_old'] = $_POST;
            $this->redirect('/roles/create');
        }

        try {
            $id = RoleManager::create($name, $description, $permissions);
            Audit::log('roles.created', 'roles', $id);
            $_SESSION['_flash']['success'] = 'Rol creado correctamente.';
            $this->redirect('/roles/' . $id . '/edit');
        } catch (PDOException) {
            $_SESSION['_flash']['error'] = 'No fue posible crear el rol. Verifica que el nombre no esté repetido y que la migración de roles esté aplicada.';
            $_SESSION['_old'] = $_POST;
            $this->redirect('/roles/create');
        }
    }

    public function edit(string $id): void
    {
        Auth::requirePermission('security', 'manage');
        $role = RoleManager::find($id);
        if ($role === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Rol no encontrado', 'activeMenu' => 'roles']);
            return;
        }
        $this->view('roles/form', [
            'title' => 'Editar rol',
            'activeMenu' => 'roles',
            'role' => $role,
            'catalog' => RoleManager::permissionCatalog(),
            'mode' => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        Auth::requirePermission('security', 'manage');
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['_flash']['error'] = 'La sesión del formulario expiró.';
            $this->redirect('/roles/' . rawurlencode($id) . '/edit');
        }
        $role = RoleManager::find($id);
        if ($role === null) {
            $this->redirect('/roles');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $permissions = $this->permissionsFromRequest();
        $active = isset($_POST['active']);

        if ((int) ($role['protected'] ?? 0) === 1) {
            $name = (string) $role['name'];
            $active = true;
            if ((string) $role['name'] === 'Super Administrador') $permissions = ['*'];
        }
        if ($name === '' || $permissions === []) {
            $_SESSION['_flash']['error'] = 'El rol debe tener nombre y al menos un permiso.';
            $this->redirect('/roles/' . rawurlencode($id) . '/edit');
        }
        try {
            RoleManager::update($id, $name, $description, $permissions, $active);
            Audit::log('roles.updated', 'roles', $id);
            $_SESSION['_flash']['success'] = 'Rol y permisos actualizados.';
            $this->redirect('/roles');
        } catch (PDOException) {
            $_SESSION['_flash']['error'] = 'No fue posible guardar el rol. Revisa que el nombre no esté duplicado.';
            $this->redirect('/roles/' . rawurlencode($id) . '/edit');
        }
    }

    private function permissionsFromRequest(): array
    {
        $raw = $_POST['permissions'] ?? [];
        if (!is_array($raw)) return [];
        $allowed = array_keys(RoleManager::permissionCatalog());
        $permissions = [];
        foreach ($raw as $permission) {
            $permission = trim((string) $permission);
            $base = explode('.', $permission, 2)[0];
            if (in_array($base, $allowed, true) && preg_match('/^[a-z0-9-]+(?:\.(?:view|manage|own))?$/', $permission)) {
                $permissions[] = $permission;
            }
        }
        return array_values(array_unique($permissions));
    }
}
