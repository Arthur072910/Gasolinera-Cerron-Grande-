<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar &middot; <?= NOMBRE_SISTEMA ?></title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">

<div class="login-marco"><span></span><span></span><span></span><span></span></div>

<div class="login-marca">
    <span class="login-marca__punto"></span>
    Base de datos conectada
</div>

<div class="login-reloj" id="login-reloj">--:--:--</div>
<div class="login-terminal-id">TERMINAL 01 &middot; V1.0</div>

<div class="login-tarjeta">
    <div class="login-tarjeta__barra"></div>
    <span class="login-tarjeta__esquina-a"></span>
    <span class="login-tarjeta__esquina-b"></span>

    <div class="login-icono">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2C12 2 5 10.5 5 15a7 7 0 0 0 14 0c0-4.5-7-13-7-13Z"></path>
        </svg>
    </div>

    <div class="login-eyebrow">Terminal de acceso</div>
    <h1 class="login-titulo">El <span>Cerron</span> Grande</h1>
    <div class="login-subt">Sistema POS &middot; ingresa tu PIN para continuar</div>

    <?php if (!empty($errorLogin)): ?>
        <div class="login-alerta">
            <span class="login-alerta__icono">&#9888;</span>
            <span><?= htmlspecialchars($errorLogin) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="index.php?accion=login" id="form-login">
        <label class="login-campo-label" for="pin">PIN de acceso</label>

        <div class="login-pin" <?= !empty($errorLogin) ? "data-con-error='1'" : '' ?>>
            <input type="password" id="pin" name="pin" class="login-pin__input"
                   inputmode="numeric" autocomplete="off" maxlength="6" required>
            <span class="login-pin__caja"></span>
            <span class="login-pin__caja"></span>
            <span class="login-pin__caja"></span>
            <span class="login-pin__caja"></span>
            <span class="login-pin__caja"></span>
            <span class="login-pin__caja"></span>
        </div>

        <div class="login-teclado" aria-hidden="true">
            <button type="button" data-tecla="1">1</button>
            <button type="button" data-tecla="2">2</button>
            <button type="button" data-tecla="3">3</button>
            <button type="button" data-tecla="4">4</button>
            <button type="button" data-tecla="5">5</button>
            <button type="button" data-tecla="6">6</button>
            <button type="button" data-tecla="7">7</button>
            <button type="button" data-tecla="8">8</button>
            <button type="button" data-tecla="9">9</button>
            <button type="button" class="login-teclado__aux" data-tecla="limpiar">C</button>
            <button type="button" data-tecla="0">0</button>
            <button type="button" class="login-teclado__aux" data-tecla="borrar">&larr;</button>
        </div>

        <button type="submit" class="login-btn" id="btn-login">
            <span class="login-btn__spinner"></span>
            <span class="login-btn__texto">Ingresar</span>
            <span class="login-btn__flecha">&rarr;</span>
        </button>

        <div class="login-progreso" id="login-progreso"><div class="login-progreso__relleno" id="login-progreso-relleno"></div></div>
        <div class="login-estado" id="login-estado"></div>
    </form>
</div>

<script src="assets/js/login.js"></script>
</body>
</html>
