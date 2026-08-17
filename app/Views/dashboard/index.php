<?php
use App\Core\Auth;

$money = static fn(mixed $value): string => '$' . number_format((float) $value, 0, ',', '.');
$kpis = [
    ['label'=>'Viajes activos','value'=>$metrics['activeTrips'],'icon'=>'bi-signpost-split','tone'=>'info','note'=>'Operación en curso','url'=>'/modules/trips'],
    ['label'=>'Facturas pendientes','value'=>$metrics['pendingInvoices'],'icon'=>'bi-receipt','tone'=>'warning','note'=>'Con saldo por cobrar','url'=>'/modules/customer-invoices'],
    ['label'=>'Equipos en taller','value'=>$metrics['workshopEquipment'],'icon'=>'bi-tools','tone'=>'danger','note'=>'Disponibilidad de flota','url'=>'/modules/equipment'],
    ['label'=>'Alertas críticas','value'=>$metrics['criticalAlerts'],'icon'=>'bi-exclamation-octagon','tone'=>'danger','note'=>'Requieren atención','url'=>'/modules/alerts'],
];
$quickModules = [
    'schedules'=>'Nueva programación','trips'=>'Registrar viaje','fuel'=>'Registrar combustible',
    'work-orders'=>'Abrir orden de trabajo','customer-invoices'=>'Emitir factura','incidents'=>'Reportar incidente',
];
?>
<div class="page-intro">
    <div><span class="section-kicker">Visión ejecutiva</span><h2>Centro de Control</h2><p>Indicadores operacionales, financieros y de cumplimiento actualizados en tiempo real.</p></div>
    <div class="page-date"><i class="bi bi-calendar3"></i><span><small>Fecha operacional</small><strong><?= e(date('d/m/Y')) ?></strong></span></div>
</div>

<section class="row g-3 mb-4" aria-label="Indicadores principales">
    <?php foreach ($kpis as $item): ?>
        <div class="col-sm-6 col-xxl-3">
            <a class="kpi-card" href="<?= e(url($item['url'])) ?>">
                <span class="kpi-icon tone-<?= e($item['tone']) ?>"><i class="bi <?= e($item['icon']) ?>"></i></span>
                <span class="kpi-content"><small><?= e($item['label']) ?></small><strong><?= e($item['value']) ?></strong><span><?= e($item['note']) ?><i class="bi bi-arrow-up-right"></i></span></span>
            </a>
        </div>
    <?php endforeach; ?>
</section>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <section class="app-card h-100">
            <div class="card-heading"><div><span class="section-kicker">Resumen mensual</span><h3>Resultado operacional</h3></div><span class="period-chip"><i class="bi bi-calendar2-week"></i><?= e(date('m/Y')) ?></span></div>
            <div class="financial-grid">
                <div><span>Facturación</span><strong><?= e($money($financial['billed'])) ?></strong><small>Documentos no anulados</small></div>
                <div><span>Por cobrar</span><strong><?= e($money($financial['receivable'])) ?></strong><small>Cartera con saldo</small></div>
                <div><span>Costos y gastos</span><strong><?= e($money($financial['expenses'])) ?></strong><small>Movimientos del período</small></div>
                <div class="financial-result"><span>Margen preliminar</span><strong><?= e($money((float) $financial['billed'] - (float) $financial['expenses'])) ?></strong><small>Antes de ajustes de cierre</small></div>
            </div>
            <?php $maximum = max(1, (float) $financial['billed'], (float) $financial['receivable'], (float) $financial['expenses']); ?>
            <div class="mini-chart" aria-label="Comparación financiera">
                <?php foreach ([['Facturación',$financial['billed'],'primary'],['Por cobrar',$financial['receivable'],'warning'],['Costos',$financial['expenses'],'danger']] as [$label,$value,$tone]): ?>
                    <div class="chart-row"><span><?= e($label) ?></span><div class="progress" role="progressbar" aria-valuenow="<?= e($value) ?>" aria-valuemin="0" aria-valuemax="<?= e($maximum) ?>"><div class="progress-bar bg-<?= e($tone) ?>" style="width: <?= e((float) $value / $maximum * 100) ?>%"></div></div><strong><?= e($money($value)) ?></strong></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="app-card h-100">
            <div class="card-heading"><div><span class="section-kicker">Gestión diaria</span><h3>Accesos rápidos</h3></div></div>
            <div class="quick-grid">
                <?php foreach ($quickModules as $slug => $label): if (!isset($modules[$slug]) || !Auth::can($modules[$slug]['area'], 'manage')) continue; ?>
                    <a href="<?= e(url('/modules/' . $slug . '/create')) ?>"><span><i class="bi <?= e($modules[$slug]['icon']) ?>"></i></span><strong><?= e($label) ?></strong><i class="bi bi-chevron-right"></i></a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <section class="app-card h-100">
            <div class="card-heading"><div><span class="section-kicker">Riesgos y vencimientos</span><h3>Alertas prioritarias</h3></div><a href="<?= e(url('/modules/alerts')) ?>">Ver todas</a></div>
            <?php if ($alerts === []): ?><div class="empty-compact"><i class="bi bi-shield-check"></i><div><strong>Sin alertas activas</strong><span>No existen riesgos pendientes registrados.</span></div></div>
            <?php else: ?><div class="alert-list"><?php foreach ($alerts as $alert): ?>
                <a href="<?= e(url('/modules/alerts/' . $alert['id'])) ?>"><span class="severity-dot severity-<?= e(strtolower(str_replace(['í',' '], ['i','-'], $alert['severity']))) ?>"></span><span><strong><?= e($alert['title']) ?></strong><small><?= e($alert['severity']) ?> · <?= e($alert['due_at'] ? date('d/m/Y H:i', strtotime($alert['due_at'])) : 'Sin vencimiento') ?></small></span><i class="bi bi-chevron-right"></i></a>
            <?php endforeach; ?></div><?php endif; ?>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="app-card h-100">
            <div class="card-heading"><div><span class="section-kicker">Trazabilidad</span><h3>Actividad reciente</h3></div><span class="audit-badge"><i class="bi bi-shield-lock"></i>Auditoría activa</span></div>
            <?php if ($activity === []): ?><div class="empty-compact"><i class="bi bi-clock-history"></i><div><strong>Aún no hay actividad</strong><span>Las acciones relevantes aparecerán en esta bitácora.</span></div></div>
            <?php else: ?><div class="timeline"><?php foreach ($activity as $entry): ?>
                <div class="timeline-item"><span class="timeline-icon"><i class="bi bi-check2"></i></span><div><strong><?= e($entry['full_name'] ?? 'Sistema') ?></strong><span><?= e($entry['action']) ?> · <?= e($entry['module']) ?></span></div><time><?= e(date('d/m H:i', strtotime($entry['created_at']))) ?></time></div>
            <?php endforeach; ?></div><?php endif; ?>
        </section>
    </div>
</div>
