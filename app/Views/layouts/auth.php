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
    <title><?= e($title ?? 'Acceso') ?> · BGV Enterprise</title>
    <link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
    <link rel="icon" href="<?= e(asset('/assets/icons/app-icon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(asset('/assets/icons/apple-touch-icon.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset('/assets/styles.css?v=20260817-pwa')) ?>">
</head>
<body class="auth-body">
<main class="auth-shell">
    <section class="auth-visual d-none d-lg-flex">
        <div class="auth-brand"><span class="brand-mark"><i class="bi bi-boxes"></i></span><span><strong>BGV Enterprise</strong><small>Foundation 1.0</small></span></div>
        <div class="auth-message">
            <span class="auth-kicker">Plataforma empresarial integrada</span>
            <h1>Control total de la operación, en una sola vista.</h1>
            <p>Comercial, transporte, flota, finanzas, personas y cumplimiento conectados con trazabilidad de principio a fin.</p>
            <div class="auth-pill-row"><span><i class="bi bi-shield-check"></i> Acceso por roles</span><span><i class="bi bi-clock-history"></i> Auditoría completa</span></div>
        </div>
        <p class="auth-version">Documento Maestro v1.0 · PHP MVC + MySQL</p>
    </section>
    <section class="auth-panel">
        <div class="auth-panel-inner">
            <div class="auth-mobile-brand d-lg-none"><span class="brand-mark"><i class="bi bi-boxes"></i></span><strong>BGV Enterprise</strong></div>
            <?php if (!empty($flash['error'])): ?><div class="alert alert-danger app-alert"><i class="bi bi-exclamation-octagon-fill"></i><span><?= e($flash['error']) ?></span></div><?php endif; ?>
            <?php if (!empty($flash['errors'])): ?><div class="alert alert-danger app-alert"><i class="bi bi-exclamation-octagon-fill"></i><div><?php foreach ((array) $flash['errors'] as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div></div><?php endif; ?>
            <?= $content ?>
            <button class="btn btn-outline-primary w-100 mt-3 d-none" type="button" data-pwa-install>
                <i class="bi bi-download"></i><span>Instalar BGV Enterprise</span>
            </button>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="<?= e(asset('/assets/app.js?v=20260817-pwa')) ?>" defer></script>
</body>
</html>
