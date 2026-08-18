<div class="page-intro page-intro-actions">
    <div>
        <p class="topbar-eyebrow mb-1">Seguridad y acceso</p>
        <h2>Roles y permisos</h2>
        <p>Define qué módulos puede ver y administrar cada tipo de usuario.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/roles/create')) ?>"><i class="bi bi-plus-lg"></i><span>Nuevo rol</span></a>
</div>

<section class="app-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Rol</th><th>Descripción</th><th>Permisos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <?php $permissions = $role['permissions'] ?? []; ?>
                <tr>
                    <td><strong><?= e($role['name']) ?></strong><?php if ((int) ($role['protected'] ?? 0) === 1): ?><div><span class="badge text-bg-light border">Protegido</span></div><?php endif; ?></td>
                    <td class="text-secondary"><?= e($role['description'] ?? '—') ?></td>
                    <td><span class="badge rounded-pill text-bg-light border"><?= in_array('*', $permissions, true) ? 'Acceso total' : e((string) count($permissions)) . ' permisos' ?></span></td>
                    <td><?= (int) ($role['active'] ?? 0) === 1 ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>' ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/roles/' . rawurlencode((string) $role['id']) . '/edit')) ?>"><i class="bi bi-pencil-square"></i> Editar</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($roles === []): ?><tr><td colspan="5" class="text-center py-5 text-secondary">No hay roles registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
