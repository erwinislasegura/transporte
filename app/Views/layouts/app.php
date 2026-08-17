<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'BGV Enterprise') ?> · BGV Enterprise</title>
    <link rel="stylesheet" href="<?= e(asset('/assets/styles.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside>
        <h1>BGV Enterprise</h1>
        <p>Foundation 1.0</p>
        <nav>
            <a class="<?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/')) ?>">Dashboard</a>
            <a class="<?= ($activeMenu ?? '') === 'clients' ? 'active' : '' ?>" href="<?= e(url('/clients')) ?>">Clientes</a>
        </nav>
    </aside>
    <main>
        <?= $content ?>
    </main>
</div>
<script src="<?= e(asset('/assets/app.js')) ?>" defer></script>
</body>
</html>
