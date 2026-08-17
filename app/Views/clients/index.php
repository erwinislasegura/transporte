<div class="page-heading">
    <h2>Clientes</h2>
    <a class="button" href="<?= e(url('/clients/create')) ?>">Nuevo cliente</a>
</div>

<?php if (!empty($databaseError)): ?>
    <p class="error"><?= e($databaseError) ?></p>
<?php endif; ?>

<?php if (!empty($flash['success'])): ?>
    <p class="success"><?= e($flash['success']) ?></p>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Código</th><th>RUT</th><th>Razón social</th><th>Pago</th><th>OC</th><th>HES</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($clients as $client): ?>
            <tr>
                <td><a class="table-link" href="<?= e(url('/clients/' . $client['id'])) ?>"><?= e($client['code']) ?></a></td>
                <td><?= e($client['rut']) ?></td>
                <td><?= e($client['business_name']) ?></td>
                <td><?= e($client['payment_condition']) ?></td>
                <td><?= e($client['requires_oc']) ?></td>
                <td><?= e($client['requires_hes']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($clients === []): ?>
            <tr><td colspan="6" class="empty">No hay clientes registrados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
