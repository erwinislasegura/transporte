<?php
$isEdit = $mode === 'edit';
$selected = old('permissions', $role['permissions'] ?? []);
if (!is_array($selected)) $selected = $role['permissions'] ?? [];
$isProtected = (int) ($role['protected'] ?? 0) === 1;
?>
<div class="page-intro page-intro-actions">
    <div><a class="back-link" href="<?= e(url('/roles')) ?>"><i class="bi bi-arrow-left"></i>Roles y permisos</a><h2><?= $isEdit ? 'Editar rol' : 'Nuevo rol' ?></h2><p>Selecciona los accesos visibles y las acciones que podrá realizar este rol.</p></div>
</div>
<form method="post" action="<?= e($isEdit ? url('/roles/' . rawurlencode((string) $role['id'])) : url('/roles')) ?>" class="record-form">
    <?= csrf_field() ?>
    <section class="app-card form-card mb-3">
        <div class="row g-3">
            <div class="col-md-5"><label class="form-label" for="name">Nombre del rol *</label><input class="form-control" id="name" name="name" value="<?= e(old('name', (string) ($role['name'] ?? ''))) ?>" required <?= $isProtected ? 'readonly' : '' ?>></div>
            <div class="col-md-7"><label class="form-label" for="description">Descripción</label><input class="form-control" id="description" name="description" value="<?= e(old('description', (string) ($role['description'] ?? ''))) ?>"></div>
            <?php if ($isEdit): ?><div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= (int) ($role['active'] ?? 1) === 1 ? 'checked' : '' ?> <?= $isProtected ? 'disabled' : '' ?>><label class="form-check-label" for="active">Rol activo</label><?php if ($isProtected): ?><input type="hidden" name="active" value="1"><?php endif; ?></div></div><?php endif; ?>
        </div>
    </section>

    <section class="app-card form-card">
        <div class="form-section-heading"><span><i class="bi bi-shield-lock"></i></span><div><h3>Permisos por área</h3><p>“Administrar” incluye crear y editar. “Solo propios” limita el acceso a registros asociados al usuario cuando el módulo lo soporte.</p></div></div>
        <?php if ($isProtected && ($role['name'] ?? '') === 'Super Administrador'): ?>
            <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>El Super Administrador siempre conserva acceso total al sistema.</div>
            <input type="hidden" name="permissions[]" value="*">
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle role-permissions-table">
                    <thead><tr><th>Área</th><th>Grupo</th><th>Ver</th><th>Administrar</th><th>Solo propios</th></tr></thead>
                    <tbody>
                    <?php foreach ($catalog as $area => $meta):
                        $full = in_array($area, $selected, true);
                        $view = $full || in_array($area . '.view', $selected, true);
                        $manage = $full || in_array($area . '.manage', $selected, true);
                        $own = in_array($area . '.own', $selected, true);
                    ?>
                        <tr>
                            <td><strong><?= e($meta['label']) ?></strong></td><td class="text-secondary"><?= e($meta['group']) ?></td>
                            <td><input class="form-check-input" type="checkbox" name="permissions[]" value="<?= e($area . '.view') ?>" <?= $view ? 'checked' : '' ?>></td>
                            <td><input class="form-check-input" type="checkbox" name="permissions[]" value="<?= e($area . '.manage') ?>" <?= $manage ? 'checked' : '' ?>></td>
                            <td><input class="form-check-input" type="checkbox" name="permissions[]" value="<?= e($area . '.own') ?>" <?= $own ? 'checked' : '' ?>></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <div class="sticky-actions"><a class="btn btn-light" href="<?= e(url('/roles')) ?>">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i><span>Guardar rol</span></button></div>
</form>
