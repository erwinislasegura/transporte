<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Database\Connection;
use App\Core\Id;
use App\Core\Security;
use PDOException;

final class AuthController extends Controller
{
    public function login(): void
    {
        if (!Auth::hasUsers()) $this->redirect('/setup');
        if (Auth::user() !== null) $this->redirect('/');
        $this->view('auth/login', ['title' => 'Iniciar sesión'], 'auth');
    }

    public function authenticate(): void
    {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['_flash']['error'] = 'La sesión del formulario expiró.';
            $this->redirect('/login');
        }
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (!Auth::attempt($identity, $password)) {
            $_SESSION['_flash']['error'] = 'Usuario, correo o contraseña incorrectos.';
            $_SESSION['_old']['identity'] = $identity;
            $this->redirect('/login');
        }
        $this->redirect('/');
    }

    public function setup(): void
    {
        if (Auth::hasUsers()) $this->redirect('/login');
        $this->view('auth/setup', ['title' => 'Configuración inicial'], 'auth');
    }

    public function initialize(): void
    {
        if (Auth::hasUsers()) $this->redirect('/login');
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $_SESSION['_flash']['error'] = 'La sesión del formulario expiró.';
            $this->redirect('/setup');
        }
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $companyRut = trim((string) ($_POST['company_rut'] ?? ''));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($companyName === '' || $companyRut === '' || $fullName === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            $_SESSION['_flash']['error'] = 'Completa todos los datos. La contraseña debe tener al menos 10 caracteres.';
            $_SESSION['_old'] = $_POST;
            $this->redirect('/setup');
        }

        $db = Connection::connection();
        try {
            $db->beginTransaction();
            $company = $db->query('SELECT id FROM companies ORDER BY created_at LIMIT 1')->fetch();
            $companyId = $company['id'] ?? Id::uuid();
            if ($company === false) {
                $statement = $db->prepare('INSERT INTO companies (id, rut, legal_name, trade_name, active) VALUES (:id, :rut, :name, :name, 1)');
                $statement->execute(['id' => $companyId, 'rut' => $companyRut, 'name' => $companyName]);
            } else {
                $statement = $db->prepare('UPDATE companies SET rut = :rut, legal_name = :name, trade_name = :name WHERE id = :id');
                $statement->execute(['id' => $companyId, 'rut' => $companyRut, 'name' => $companyName]);
            }
            $userId = Id::uuid();
            $statement = $db->prepare(
                'INSERT INTO users (id, company_id, username, full_name, email, password_hash, role, active)
                 VALUES (:id, :company_id, :username, :full_name, :email, :password_hash, :role, 1)'
            );
            $statement->execute([
                'id' => $userId, 'company_id' => $companyId, 'username' => $username, 'full_name' => $fullName,
                'email' => $email, 'password_hash' => Security::hashPassword($password), 'role' => 'Super Administrador',
            ]);
            $db->commit();
            Audit::log('system.initialized', 'system', $userId);
            Auth::attempt($username, $password);
            $_SESSION['_flash']['success'] = 'BGV Enterprise quedó configurado correctamente.';
            $this->redirect('/');
        } catch (PDOException) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['_flash']['error'] = 'No fue posible crear la cuenta de Super Administrador. Verifica la migración MySQL y que el RUT, usuario o correo no estén repetidos.';
            $this->redirect('/setup');
        }
    }

    public function logout(): void
    {
        if (Csrf::validate($_POST['_token'] ?? null)) Auth::logout();
        $this->redirect('/login');
    }
}
