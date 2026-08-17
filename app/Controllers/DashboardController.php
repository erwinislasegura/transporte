<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Database\Connection;
use App\Core\RecordRepository;
use PDOException;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('dashboard');
        $companyId = Auth::companyId();
        $metrics = [
            'activeTrips' => RecordRepository::count('trips', $companyId, "status IN ('Asignado','Iniciado','En carga','En tránsito','En descarga')"),
            'pendingInvoices' => RecordRepository::count('customer_invoices', $companyId, "status IN ('Emitida','Aceptada','Observada') AND balance > 0"),
            'workshopEquipment' => RecordRepository::count('equipment', $companyId, "status = 'En taller'"),
            'criticalAlerts' => RecordRepository::count('alerts', $companyId, "severity = 'Crítica' AND status IN ('Abierta','En gestión')"),
            'clients' => RecordRepository::count('clients', $companyId, 'active = 1'),
            'workers' => RecordRepository::count('workers', $companyId, "status = 'Activo'"),
            'equipment' => RecordRepository::count('equipment', $companyId, "status <> 'Vendido'"),
            'openWorkOrders' => RecordRepository::count('work_orders', $companyId, "status NOT IN ('Cerrada','Anulada')"),
        ];
        $financial = ['billed' => 0.0, 'receivable' => 0.0, 'expenses' => 0.0];
        $alerts = [];
        $activity = [];
        try {
            $statement = Connection::connection()->prepare(
                "SELECT COALESCE(SUM(total),0) billed, COALESCE(SUM(balance),0) receivable
                 FROM customer_invoices WHERE company_id = :company_id AND issue_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') AND status <> 'Anulada'"
            );
            $statement->execute(['company_id' => $companyId]);
            $financial = array_merge($financial, $statement->fetch() ?: []);
            $statement = Connection::connection()->prepare(
                "SELECT COALESCE(SUM(amount),0) expenses FROM financial_movements
                 WHERE company_id = :company_id AND movement_type IN ('Costo','Gasto') AND movement_date >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01') AND status <> 'Anulado'"
            );
            $statement->execute(['company_id' => $companyId]);
            $financial['expenses'] = (float) $statement->fetchColumn();
            $statement = Connection::connection()->prepare(
                "SELECT id, title, severity, due_at, status FROM alerts WHERE company_id = :company_id AND status IN ('Abierta','En gestión') ORDER BY FIELD(severity,'Crítica','Alta','Advertencia','Info'), due_at LIMIT 6"
            );
            $statement->execute(['company_id' => $companyId]);
            $alerts = $statement->fetchAll();
            $statement = Connection::connection()->prepare(
                "SELECT a.action, a.module, a.created_at, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE a.company_id = :company_id ORDER BY a.created_at DESC LIMIT 8"
            );
            $statement->execute(['company_id' => $companyId]);
            $activity = $statement->fetchAll();
        } catch (PDOException) {
            // El dashboard continúa disponible durante la aplicación de la migración.
        }
        $this->view('dashboard/index', [
            'title' => 'Centro de Control',
            'activeMenu' => 'dashboard',
            'metrics' => $metrics,
            'financial' => $financial,
            'alerts' => $alerts,
            'activity' => $activity,
            'modules' => require BASE_PATH . '/config/modules.php',
        ]);
    }
}
