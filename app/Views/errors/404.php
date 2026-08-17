<section class="panel error-page">
    <strong>404</strong>
    <h2><?= e($title ?? 'Página no encontrada') ?></h2>
    <p>El recurso solicitado no existe o fue movido.</p>
    <a class="button" href="<?= e(url('/')) ?>">Volver al dashboard</a>
</section>
