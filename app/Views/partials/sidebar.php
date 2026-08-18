<?php
$groupIcons = [
    'Configuración' => 'bi-sliders2', 'Comercial' => 'bi-briefcase', 'Operaciones' => 'bi-signpost-split',
    'Flota y Taller' => 'bi-truck-front', 'Compras' => 'bi-cart3', 'Facturación y Finanzas' => 'bi-cash-stack',
    'RR.HH.' => 'bi-people', 'HSE y Ambiente' => 'bi-shield-check', 'Documentos y Control' => 'bi-folder2-open',
];
?>
<div class="sidebar-brand">
    <span class="brand-mark"><i class="bi bi-boxes"></i></span>
    <span><strong>BGV Enterprise</strong><small>Control operacional</small></span>
</div>
<nav class="sidebar-nav" aria-label="Navegación principal">
    <a class="sidebar-link <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/')) ?>">
        <i class="bi bi-grid-1x2-fill"></i><span>Centro de Control</span>
    </a>
    <?php if (\App\Core\Auth::can('bi')): ?><a class="sidebar-link <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>" href="<?= e(url('/reports')) ?>"><i class="bi bi-bar-chart-line"></i><span>Reportes ejecutivos</span></a><?php endif; ?>
    <?php if (\App\Core\Auth::can('security', 'manage')): ?>
        <a class="sidebar-link <?= ($activeMenu ?? '') === 'roles' ? 'active' : '' ?>" href="<?= e(url('/roles')) ?>"><i class="bi bi-shield-lock"></i><span>Roles y permisos</span></a>
    <?php endif; ?>
    <?php foreach ($groups as $group => $items):
        $expanded = array_key_exists((string) ($activeMenu ?? ''), $items);
        $collapseId = 'nav-' . substr(sha1($group), 0, 8);
    ?>
        <button class="sidebar-group <?= $expanded ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($collapseId) ?>" aria-expanded="<?= $expanded ? 'true' : 'false' ?>">
            <span><i class="bi <?= e($groupIcons[$group] ?? 'bi-folder2') ?>"></i><?= e($group) ?></span><i class="bi bi-chevron-down group-chevron"></i>
        </button>
        <div class="collapse <?= $expanded ? 'show' : '' ?>" id="<?= e($collapseId) ?>">
            <div class="sidebar-subnav">
                <?php foreach ($items as $slug => $item): ?>
                    <a class="sidebar-link sidebar-sublink <?= ($activeMenu ?? '') === $slug ? 'active' : '' ?>" href="<?= e(url('/modules/' . $slug)) ?>">
                        <i class="bi <?= e($item['icon']) ?>"></i><span><?= e($item['title']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</nav>
<div class="sidebar-status">
    <span class="status-dot"></span><span><strong>Sistema operativo</strong><small>MySQL · Auditoría activa</small></span>
</div>
