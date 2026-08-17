<div class="page-heading">
    <h2>Nuevo cliente</h2>
    <a class="button button-secondary" href="<?= e(url('/clients')) ?>">Volver</a>
</div>

<?php if (!empty($databaseError)): ?><p class="error"><?= e($databaseError) ?></p><?php endif; ?>
<?php if (!empty($flash['errors'])): ?>
    <div class="form-alert error"><strong>Revise los siguientes datos:</strong><ul><?php foreach ($flash['errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form class="panel form-grid" method="post" action="<?= e(url('/clients')) ?>">
    <?= csrf_field() ?>
    <label class="full">Empresa
        <select name="company_id" required>
            <option value="">Seleccione una empresa</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= e($company['id']) ?>" <?= old('company_id') === $company['id'] ? 'selected' : '' ?>><?= e($company['legal_name']) ?> · <?= e($company['rut']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Código<input name="code" minlength="3" maxlength="30" value="<?= e(old('code')) ?>" required></label>
    <label>RUT<input name="rut" minlength="8" maxlength="12" value="<?= e(old('rut')) ?>" placeholder="76.123.456-7" required></label>
    <label class="full">Razón social<input name="business_name" maxlength="180" value="<?= e(old('business_name')) ?>" required></label>
    <label class="full">Condición de pago<input name="payment_condition" value="<?= e(old('payment_condition', 'Transferencia 30 días')) ?>" required></label>
    <label>Requiere OC<select name="requires_oc"><?php foreach (['Opcional', 'Sí', 'No'] as $option): ?><option <?= old('requires_oc', 'Opcional') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
    <label>Requiere HES<select name="requires_hes"><?php foreach (['Opcional', 'Sí', 'No'] as $option): ?><option <?= old('requires_hes', 'Opcional') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
    <div class="full form-actions"><button class="button" type="submit">Guardar cliente</button></div>
</form>
