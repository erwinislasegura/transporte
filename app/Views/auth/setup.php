<div class="auth-heading">
    <span class="section-kicker">Configuración inicial</span>
    <h2>Activa BGV Enterprise</h2>
    <p>Crea la empresa y el usuario Super Administrador. Podrás administrar los demás accesos desde el sistema.</p>
</div>
<form method="post" action="<?= e(url('/setup')) ?>" class="auth-form">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-12"><label class="form-label" for="company_name">Razón social</label><input class="form-control form-control-lg" id="company_name" name="company_name" value="<?= e(old('company_name', 'BGV Enterprise')) ?>" required></div>
        <div class="col-md-6"><label class="form-label" for="company_rut">RUT empresa</label><input class="form-control form-control-lg" id="company_rut" name="company_rut" value="<?= e(old('company_rut')) ?>" placeholder="76.123.456-7" required></div>
        <div class="col-md-6"><label class="form-label" for="full_name">Nombre del Super Administrador</label><input class="form-control form-control-lg" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" required></div>
        <div class="col-md-6"><label class="form-label" for="username">Usuario</label><input class="form-control form-control-lg" id="username" name="username" value="<?= e(old('username', 'maestro')) ?>" autocomplete="username" required></div>
        <div class="col-md-6"><label class="form-label" for="email">Correo</label><input class="form-control form-control-lg" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required></div>
        <div class="col-12"><label class="form-label" for="password">Contraseña segura</label><div class="input-group input-group-lg"><input class="form-control" type="password" id="password" name="password" minlength="10" autocomplete="new-password" required><button class="btn btn-outline-secondary password-toggle" type="button" data-password-toggle="#password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button></div><div class="form-text">Mínimo 10 caracteres. Evita reutilizar contraseñas personales.</div></div>
    </div>
    <button class="btn btn-primary btn-lg w-100" type="submit"><span>Crear empresa y continuar</span><i class="bi bi-check2-circle"></i></button>
</form>
