<?php
$format = static function (string $column, mixed $value, array $field = [], array $relationLabels = []): string {
    if (isset($relationLabels[$column][$value])) return (string) $relationLabels[$column][$value];
    if ($value === null || $value === '') return '—';
    if (($field['type'] ?? '') === 'checkbox' || $column === 'active') return (int) $value === 1 ? 'Sí' : 'No';
    if (($field['type'] ?? '') === 'number' && in_array($column, ['total','amount','balance','net_amount','estimated_amount','estimated_cost','actual_cost','total_cost','gross_amount'], true)) return '$' . number_format((float) $value, 0, ',', '.');
    if (($field['type'] ?? '') === 'date') return date('d/m/Y', strtotime((string) $value));
    if (($field['type'] ?? '') === 'datetime-local') return date('d/m/Y H:i', strtotime((string) $value));
    return (string) $value;
};
$statusClass = static function (mixed $value): string {
    $danger = ['Anulada','Anulado','Rechazada','Rechazado','Vencida','Vencido','Crítica','Fuera de servicio','No conforme'];
    $success = ['Activo','Activa','Sí','Vigente','Aceptada','Aprobada','Aprobado','Completada','Terminado','Terminada','Cerrada','Cerrado','Disponible','Validada','Validado','Pagada','Recibida','Recibido','Conforme','Resuelta'];
    $warning = ['Pendiente','Observada','Observado','En gestión','En proceso','En taller','En reparación','Advertencia','Alta','Suspendida'];
    return in_array($value, $danger, true) ? 'danger' : (in_array($value, $success, true) ? 'success' : (in_array($value, $warning, true) ? 'warning' : 'neutral'));
};
?>
<div class="page-intro page-intro-actions">
    <div><span class="section-kicker"><?= e($module['group']) ?></span><h2><?= e($module['title']) ?></h2><p><?= e($module['subtitle']) ?></p></div>
    <?php if ($canManage): ?><a class="btn btn-primary" href="<?= e(url('/modules/' . $moduleSlug . '/create')) ?>"><i class="bi bi-plus-lg"></i>Nuevo <?= e(strtolower($module['singular'])) ?></a><?php endif; ?>
</div>
<?php if (!empty($databaseError)): ?><div class="migration-callout"><span><i class="bi bi-database-exclamation"></i></span><div><strong>Actualización MySQL pendiente</strong><p><?= e($databaseError) ?></p><code>database/03-documento-maestro-v1.sql</code></div></div><?php endif; ?>
<section class="app-card table-card">
    <div class="table-toolbar">
        <form method="get" action="<?= e(url('/modules/' . $moduleSlug)) ?>" class="search-form" role="search"><i class="bi bi-search"></i><input name="q" value="<?= e($search) ?>" placeholder="Buscar en <?= e(strtolower($module['title'])) ?>…" aria-label="Buscar"><button class="btn btn-dark" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="btn btn-link" href="<?= e(url('/modules/' . $moduleSlug)) ?>">Limpiar</a><?php endif; ?></form>
        <span class="record-count"><strong><?= e(count($records)) ?></strong> registros visibles</span>
    </div>
    <div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><?php foreach ($module['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th class="text-end">Acciones</th></tr></thead><tbody>
        <?php if ($records === []): ?><tr><td colspan="<?= e(count($module['columns']) + 1) ?>"><div class="empty-state"><span><i class="bi <?= e($module['icon']) ?>"></i></span><strong>No hay registros para mostrar</strong><p><?= $search !== '' ? 'Prueba con otro criterio de búsqueda.' : 'Comienza creando el primer registro de este módulo.' ?></p><?php if ($canManage && $search === '' && empty($databaseError)): ?><a class="btn btn-outline-primary" href="<?= e(url('/modules/' . $moduleSlug . '/create')) ?>">Crear registro</a><?php endif; ?></div></td></tr>
        <?php else: foreach ($records as $record): ?><tr><?php foreach ($module['columns'] as $column => $label): $displayValue = $format($column, $record[$column] ?? null, $module['fields'][$column] ?? [], $relationLabels); ?><td><?php if (in_array($column, ['status','active','severity','result'], true)): ?><span class="status-badge status-<?= e($statusClass($displayValue)) ?>"><span></span><?= e($displayValue) ?></span><?php else: ?><span class="cell-value <?= $column === 'code' ? 'cell-code' : '' ?>"><?= e($displayValue) ?></span><?php endif; ?></td><?php endforeach; ?><td class="text-end"><div class="dropdown"><button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-label="Acciones"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="<?= e(url('/modules/' . $moduleSlug . '/' . $record['id'])) ?>"><i class="bi bi-eye me-2"></i>Ver detalle</a></li><?php if ($canManage): ?><li><a class="dropdown-item" href="<?= e(url('/modules/' . $moduleSlug . '/' . $record['id'] . '/edit')) ?>"><i class="bi bi-pencil me-2"></i>Editar</a></li><?php endif; ?></ul></div></td></tr><?php endforeach; endif; ?>
    </tbody></table></div>
</section>
