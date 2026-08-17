<?php
$isEdit = $mode === 'edit';
$value = static fn(string $column, array $record): string => old($column, isset($record[$column]) ? (string) $record[$column] : '');
?>
<div class="page-intro page-intro-actions"><div><a class="back-link" href="<?= e(url('/modules/' . $moduleSlug)) ?>"><i class="bi bi-arrow-left"></i><?= e($module['title']) ?></a><h2><?= $isEdit ? 'Editar' : 'Nuevo' ?> <?= e(strtolower($module['singular'])) ?></h2><p>Completa la información requerida. Cada cambio quedará registrado en la bitácora de auditoría.</p></div></div>
<form method="post" action="<?= e($isEdit ? url('/modules/' . $moduleSlug . '/' . $record['id']) : url('/modules/' . $moduleSlug)) ?>" enctype="multipart/form-data" class="record-form" data-module="<?= e($moduleSlug) ?>">
    <?= csrf_field() ?>
    <section class="app-card form-card"><div class="form-section-heading"><span><i class="bi <?= e($module['icon']) ?>"></i></span><div><h3>Información general</h3><p>Los campos marcados con <em>*</em> son obligatorios.</p></div></div><div class="row g-4">
        <?php foreach ($module['fields'] as $column => $field): $type = $field['type'] ?? 'text'; $required = ($field['required'] ?? false) || (!$isEdit && ($field['required_on_create'] ?? false)); $current = $value($column, $record); $wide = in_array($type, ['textarea','file'], true) ? 'col-12' : 'col-md-6 col-xl-4'; ?>
            <div class="<?= e($wide) ?>"><?php if ($type === 'checkbox'): ?><div class="form-check form-switch switch-card"><input class="form-check-input" type="checkbox" role="switch" id="<?= e($column) ?>" name="<?= e($column) ?>" value="1" <?= ($current !== '' ? (int) $current === 1 : (int) ($field['default'] ?? 0) === 1) ? 'checked' : '' ?>><label class="form-check-label" for="<?= e($column) ?>"><strong><?= e($field['label']) ?></strong><small>Activa o desactiva esta condición</small></label></div><?php else: ?>
                <label class="form-label" for="<?= e($column) ?>"><?= e($field['label']) ?><?= $required ? ' *' : '' ?></label>
                <?php if ($type === 'select'): ?><select class="form-select" id="<?= e($column) ?>" name="<?= e($column) ?>" <?= $required ? 'required' : '' ?>><option value="">Seleccionar…</option><?php $items = $field['options'] ?? $options[$column] ?? []; foreach ($items as $optionValue => $optionLabel): ?><option value="<?= e($optionValue) ?>" <?= (string) $current === (string) $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option><?php endforeach; ?></select>
                <?php elseif ($type === 'textarea'): ?><textarea class="form-control" id="<?= e($column) ?>" name="<?= e($column) ?>" rows="4" <?= $required ? 'required' : '' ?>><?= e($current) ?></textarea>
                <?php elseif ($type === 'file'): ?><input class="form-control" type="file" id="<?= e($column) ?>" name="<?= e($column) ?>" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"><div class="form-text">PDF, imagen o Word; máximo 15 MB.<?= $isEdit && !empty($record[$column]) ? ' Deja vacío para conservar el archivo actual.' : '' ?></div>
                <?php else: ?><?php $displayValue = $current; if ($type === 'datetime-local' && $current !== '') $displayValue = date('Y-m-d\TH:i', strtotime($current)); ?><input class="form-control" type="<?= e($type) ?>" id="<?= e($column) ?>" name="<?= e($column) ?>" value="<?= e($displayValue) ?>" <?= isset($field['step']) ? 'step="' . e($field['step']) . '"' : '' ?> <?= $required ? 'required' : '' ?> <?= ($field['virtual'] ?? false) && $isEdit ? 'placeholder="Dejar vacío para mantener"' : '' ?>><?php endif; ?>
            <?php endif; ?></div>
        <?php endforeach; ?>
    </div></section>
    <div class="sticky-actions"><a class="btn btn-light" href="<?= e(url('/modules/' . $moduleSlug)) ?>">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i><span><?= $isEdit ? 'Guardar cambios' : 'Crear registro' ?></span></button></div>
</form>
