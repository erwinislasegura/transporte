<section class="app-card error-page">
    <strong>500</strong>
    <h2>Error interno</h2>
    <p>No fue posible procesar la solicitud. Inténtelo nuevamente o revise el registro de la aplicación.</p>
    <?php if (!empty($incidentId)): ?><p class="small text-muted mb-3">Referencia: <code><?= e($incidentId) ?></code></p><?php endif; ?>
    <?php if (!empty($operationalMessage)): ?>
        <div class="alert alert-danger text-start">
            <strong>Conexión MySQL:</strong><br><?= e($operationalMessage) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($diagnostic)): ?>
        <div class="alert alert-warning text-start">
            <strong>Diagnóstico local:</strong><br><code class="text-break"><?= e($diagnostic) ?></code>
            <hr>
            <small>Ejecute también <code>php scripts/diagnose.php</code> desde la carpeta del proyecto.</small>
        </div>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><i class="bi bi-arrow-clockwise"></i>Volver al Centro de Control</a>
</section>
