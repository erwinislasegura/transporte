<section class="app-card error-page">
    <strong>404</strong>
    <h2><?= e($title ?? 'Página no encontrada') ?></h2>
    <p>El recurso solicitado no existe o fue movido.</p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><i class="bi bi-house"></i>Volver al Centro de Control</a>
</section>
