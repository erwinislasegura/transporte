<div class="page-heading">
    <h2><?= e($client['business_name']) ?></h2>
    <a class="button button-secondary" href="<?= e(url('/clients')) ?>">Volver</a>
</div>
<?php if (!empty($flash['success'])): ?><p class="success"><?= e($flash['success']) ?></p><?php endif; ?>
<section class="panel detail-grid">
    <div><span>Código</span><strong><?= e($client['code']) ?></strong></div>
    <div><span>RUT</span><strong><?= e($client['rut']) ?></strong></div>
    <div><span>Condición de pago</span><strong><?= e($client['payment_condition']) ?></strong></div>
    <div><span>Requiere OC</span><strong><?= e($client['requires_oc']) ?></strong></div>
    <div><span>Requiere HES</span><strong><?= e($client['requires_hes']) ?></strong></div>
    <div><span>Estado</span><strong><?= (int) $client['active'] === 1 ? 'Activo' : 'Inactivo' ?></strong></div>
</section>
