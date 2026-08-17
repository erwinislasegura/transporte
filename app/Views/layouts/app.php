<?php

use App\Core\Auth;

$navigationModules = require BASE_PATH . '/config/modules.php';
$groups = [];
foreach ($navigationModules as $slug => $navigationModule) {
    if (Auth::can($navigationModule['area'])) {
        $groups[$navigationModule['group']][$slug] = $navigationModule;
    }
}
$currentUser = Auth::user();
?>
<!doctype html>
<html lang="es" data-base-path="<?= e(base_path()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#173846">
    <meta name="application-name" content="BGV Enterprise">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BGV Enterprise">
    <meta name="format-detection" content="telephone=no">
    <title><?= e($title ?? 'BGV Enterprise') ?> · BGV Enterprise</title>
    <link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
    <link rel="icon" href="<?= e(asset('/assets/icons/app-icon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(asset('/assets/icons/apple-touch-icon.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset('/assets/styles.css?v=20260817-pwa')) ?>">
</head>
<body class="app-body">
<div class="app-shell">
    <aside class="app-sidebar d-none d-xl-flex">
        <?php require BASE_PATH . '/app/Views/partials/sidebar.php'; ?>
    </aside>

    <div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header visually-hidden">
            <h2 id="mobileSidebarLabel">Navegación principal</h2>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <?php require BASE_PATH . '/app/Views/partials/sidebar.php'; ?>
        </div>
    </div>

    <div class="app-workspace">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <button class="btn btn-icon d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Abrir menú">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="min-w-0">
                    <p class="topbar-eyebrow mb-0">BGV Enterprise · Foundation 1.0</p>
                    <h1 class="topbar-title mb-0 text-truncate"><?= e($title ?? 'Centro de Control') ?></h1>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-icon d-none" type="button" data-pwa-install aria-label="Instalar BGV Enterprise" data-bs-toggle="tooltip" data-bs-title="Instalar aplicación">
                    <i class="bi bi-download"></i>
                </button>
                <a class="btn btn-icon position-relative" href="<?= e(url('/modules/alerts')) ?>" aria-label="Ver alertas" data-bs-toggle="tooltip" data-bs-title="Alertas y vencimientos">
                    <i class="bi bi-bell"></i>
                </a>
                <div class="dropdown">
                    <button class="btn user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="user-avatar"><?= e(strtoupper(substr((string) ($currentUser['full_name'] ?? 'U'), 0, 1))) ?></span>
                        <span class="d-none d-md-block text-start">
                            <strong><?= e($currentUser['full_name'] ?? 'Usuario') ?></strong>
                            <small><?= e($currentUser['role'] ?? '') ?></small>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">Sesión segura</h6></li>
                        <?php if (Auth::can('security')): ?><li><a class="dropdown-item" href="<?= e(url('/modules/users')) ?>"><i class="bi bi-person-gear me-2"></i>Usuarios y permisos</a></li><li><hr class="dropdown-divider"></li><?php endif; ?>
                        <li>
                            <form method="post" action="<?= e(url('/logout')) ?>">
                                <?= csrf_field() ?>
                                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-main">
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show app-alert" role="alert">
                    <i class="bi bi-check-circle-fill"></i><span><?= e($flash['success']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show app-alert" role="alert">
                    <i class="bi bi-exclamation-octagon-fill"></i><span><?= e($flash['error']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($flash['errors'])): ?>
                <div class="alert alert-danger app-alert" role="alert">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <div><?php foreach ((array) $flash['errors'] as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
        <footer class="app-footer">BGV Enterprise · Gestión integrada, segura y trazable</footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="<?= e(asset('/assets/app.js?v=20260817-pwa')) ?>" defer></script>
</body>
</html>
