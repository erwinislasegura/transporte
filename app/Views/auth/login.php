<div class="auth-heading">
    <span class="section-kicker">Acceso seguro</span>
    <h2>Bienvenido de vuelta</h2>
    <p>Ingresa con tu usuario o correo corporativo.</p>
</div>
<form method="post" action="<?= e(url('/login')) ?>" class="auth-form">
    <?= csrf_field() ?>
    <div>
        <label class="form-label" for="identity">Usuario o correo</label>
        <div class="input-group input-group-lg"><span class="input-group-text"><i class="bi bi-person"></i></span><input class="form-control" id="identity" name="identity" value="<?= e(old('identity')) ?>" autocomplete="username" required autofocus></div>
    </div>
    <div>
        <label class="form-label" for="password">Contraseña</label>
        <div class="input-group input-group-lg"><span class="input-group-text"><i class="bi bi-lock"></i></span><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required><button class="btn btn-outline-secondary password-toggle" type="button" data-password-toggle="#password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button></div>
    </div>
    <button class="btn btn-primary btn-lg w-100" type="submit"><span>Ingresar a la plataforma</span><i class="bi bi-arrow-right"></i></button>
</form>
<p class="auth-help"><i class="bi bi-info-circle"></i> Si es la primera instalación, el sistema abrirá el asistente inicial.</p>
