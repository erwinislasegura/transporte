<section class="app-card error-page">
    <strong>500</strong>
    <h2>Error interno</h2>
    <p>No fue posible procesar la solicitud. Inténtelo nuevamente o revise el registro de la aplicación.</p>
    <?php if (!empty($diagnostic)): ?><div class="alert alert-warning text-start"><strong>Diagnóstico:</strong><br><code><?= e($diagnostic) ?></code></div><?php endif; ?>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><i class="bi bi-arrow-clockwise"></i>Volver al Centro de Control</a>
</section>
