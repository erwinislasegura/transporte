<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Database\Connection;
use PDO;
use PDOException;

final class ReportController extends Controller
{
    public function index(): void
    {
        Auth::requirePermission('bi');
        [$from, $to] = $this->period();
        [$metrics, $clients, $error] = $this->data($from, $to);
        $this->view('reports/index', [
            'title' => 'Reportes ejecutivos', 'activeMenu' => 'reports', 'from' => $from, 'to' => $to,
            'metrics' => $metrics, 'clients' => $clients, 'databaseError' => $error,
        ]);
    }

    public function export(): never
    {
        Auth::requirePermission('bi');
        [$from, $to] = $this->period();
        [$metrics] = $this->data($from, $to);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bgv-reporte-' . $from . '-' . $to . '.csv"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['BGV Enterprise', 'Reporte ejecutivo', $from . ' a ' . $to], ';');
        fputcsv($output, ['Indicador', 'Valor'], ';');
        foreach ($metrics as $metric) fputcsv($output, [$metric['label'], $metric['value']], ';');
        fclose($output);
        exit;
    }

    private function period(): array
    {
        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to = (string) ($_GET['to'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
        if ($to < $from) [$from, $to] = [$to, $from];
        return [$from, $to];
    }

    private function data(string $from, string $to): array
    {
        $companyId = Auth::companyId();
        $definitions = [
            ['Viajes terminados','trips','COUNT(*)','scheduled_at',"status = 'Terminado'",'number','bi-signpost-split'],
            ['Toneladas transportadas','trips','COALESCE(SUM(tons),0)','scheduled_at',"status <> 'Anulado'",'decimal','bi-box-seam'],
            ['Facturación','customer_invoices','COALESCE(SUM(total),0)','issue_date',"status <> 'Anulada'",'money','bi-receipt'],
            ['Saldo por cobrar','customer_invoices','COALESCE(SUM(balance),0)','issue_date',"status <> 'Anulada'",'money','bi-hourglass-split'],
            ['Litros de combustible','fuel_records','COALESCE(SUM(liters),0)','recorded_at','1=1','decimal','bi-fuel-pump'],
            ['Costo de combustible','fuel_records','COALESCE(SUM(total_cost),0)','recorded_at','1=1','money','bi-currency-dollar'],
            ['Costo de mantenimiento','work_orders','COALESCE(SUM(actual_cost),0)','opened_at',"status <> 'Anulada'",'money','bi-tools'],
            ['Horas extra','attendance','COALESCE(SUM(overtime_hours),0)','attendance_date',"status <> 'Anulada'",'decimal','bi-clock-history'],
            ['Incidentes reportados','incidents','COUNT(*)','occurred_at',"status <> 'Anulado'",'number','bi-shield-exclamation'],
            ['Eventos ambientales','environmental_events','COUNT(*)','event_date',"status <> 'Anulado'",'number','bi-tree'],
        ];
        $metrics = [];
        $clients = [];
        try {
            $db = Connection::connection();
            foreach ($definitions as [$label,$table,$expression,$dateColumn,$condition,$format,$icon]) {
                $statement = $db->prepare("SELECT {$expression} FROM {$table} WHERE company_id = :company_id AND DATE({$dateColumn}) BETWEEN :from_date AND :to_date AND {$condition}");
                $statement->execute(['company_id'=>$companyId,'from_date'=>$from,'to_date'=>$to]);
                $metrics[] = ['label'=>$label,'value'=>(float) $statement->fetchColumn(),'format'=>$format,'icon'=>$icon];
            }
            $statement = $db->prepare(
                "SELECT c.business_name, COUNT(i.id) documents, COALESCE(SUM(i.total),0) total, COALESCE(SUM(i.balance),0) balance
                 FROM customer_invoices i JOIN clients c ON c.id = i.client_id
                 WHERE i.company_id = :company_id AND i.issue_date BETWEEN :from_date AND :to_date AND i.status <> 'Anulada'
                 GROUP BY c.id, c.business_name ORDER BY total DESC LIMIT 8"
            );
            $statement->execute(['company_id'=>$companyId,'from_date'=>$from,'to_date'=>$to]);
            $clients = $statement->fetchAll(PDO::FETCH_ASSOC);
            $error = null;
        } catch (PDOException) {
            $metrics = array_map(static fn(array $definition): array => [
                'label'=>$definition[0], 'value'=>0, 'format'=>$definition[5], 'icon'=>$definition[6],
            ], $definitions);
            $error = 'Aplica la migración del Documento Maestro para habilitar todos los indicadores.';
        }
        return [$metrics, $clients, $error];
    }
}
