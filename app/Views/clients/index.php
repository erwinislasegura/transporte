<h2>Clientes</h2>

<?php if (!empty($databaseError)): ?>
    <p class="error"><?= e($databaseError) ?></p>
<?php endif; ?>

<table>
    <thead>
    <tr>
        <th>Código</th><th>RUT</th><th>Razón social</th><th>Pago</th><th>OC</th><th>HES</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($clients as $client): ?>
        <tr>
            <td><?= e($client['code']) ?></td>
            <td><?= e($client['rut']) ?></td>
            <td><?= e($client['business_name']) ?></td>
            <td><?= e($client['payment_condition']) ?></td>
            <td><?= e($client['requires_oc']) ?></td>
            <td><?= e($client['requires_hes']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
