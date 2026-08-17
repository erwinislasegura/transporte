<?php
$formatDetail = static function (string $column, mixed $value, array $field, array $relationLabels): string {
    if (isset($relationLabels[$column][$value])) return (string) $relationLabels[$column][$value];
    if ($value === null || $value === '') return 'No informado';
    if (($field['type'] ?? '') === 'checkbox') return (int) $value === 1 ? 'Sí' : 'No';
    if (($field['type'] ?? '') === 'date') return date('d/m/Y', strtotime((string) $value));
    if (($field['type'] ?? '') === 'datetime-local') return date('d/m/Y H:i', strtotime((string) $value));
    if (($field['type'] ?? '') === 'number' && preg_match('/(amount|cost|total|balance|rate|limit)/', $column)) return '$' . number_format((float) $value, 0, ',', '.');
    return (string) $value;
};
$heading = $record['code'] ?? $record['name'] ?? $record['business_name'] ?? $record['full_name'] ?? $module['singular'];
?>
<div class="page-intro page-intro-actions"><div><a class="back-link" href="<?= e(url('/modules/' . $moduleSlug)) ?>"><i class="bi bi-arrow-left"></i><?= e($module['title']) ?></a><h2><?= e($heading) ?></h2><p>Detalle completo y trazable del registro.</p></div><div class="d-flex gap-2"><?php if ($canManage): ?><a class="btn btn-outline-primary" href="<?= e(url('/modules/' . $moduleSlug . '/' . $record['id'] . '/edit')) ?>"><i class="bi bi-pencil"></i>Editar</a><?php endif; ?></div></div>
<section class="app-card detail-card"><div class="detail-hero"><span><i class="bi <?= e($module['icon']) ?>"></i></span><div><small><?= e($module['singular']) ?></small><strong><?= e($heading) ?></strong></div><span class="audit-badge"><i class="bi bi-shield-check"></i>Registro auditable</span></div><div class="detail-grid">
    <?php foreach ($module['fields'] as $column => $field): if (($field['virtual'] ?? false)) continue; $raw = $record[$column] ?? null; ?><div class="detail-field <?= ($field['type'] ?? '') === 'textarea' ? 'detail-wide' : '' ?>"><span><?= e($field['label']) ?></span><?php if (($field['type'] ?? '') === 'file' && $raw): ?><a class="btn btn-sm btn-outline-primary align-self-start" href="<?= e(url('/modules/' . $moduleSlug . '/' . $record['id'] . '/download')) ?>"><i class="bi bi-download"></i>Descargar documento</a><?php else: ?><strong><?= nl2br(e($formatDetail($column, $raw, $field, $relationLabels))) ?></strong><?php endif; ?></div><?php endforeach; ?>
    </div><div class="record-meta"><span><i class="bi bi-fingerprint"></i>ID <?= e($record['id']) ?></span><span><i class="bi bi-clock-history"></i>Creado <?= e(isset($record['created_at']) ? date('d/m/Y H:i', strtotime($record['created_at'])) : '—') ?></span></div></section>
<?php if ($canManage && (isset($module['fields']['status']) || isset($module['fields']['active']))): ?><section class="danger-zone"><div><strong>Cerrar o desactivar registro</strong><p>Se conservará la información y su historial de auditoría.</p></div><form method="post" action="<?= e(url('/modules/' . $moduleSlug . '/' . $record['id'] . '/archive')) ?>" data-confirm="¿Confirmas cerrar o desactivar este registro?"><?= csrf_field() ?><button class="btn btn-outline-danger" type="submit"><i class="bi bi-archive"></i>Cerrar registro</button></form></section><?php endif; ?>
