<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar &middot; <?= NOMBRE_SISTEMA ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-shell">
    <div class="login-caja">
        <h1 class="fuente-display">EL CERRON GRANDE</h1>
        <div class="subt">Terminal de acceso &middot; ingresa tu PIN</div>

        <?php if (!empty($errorLogin)): ?>
            <div class="aviso-error"><?= htmlspecialchars($errorLogin) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?accion=login">
            <div class="campo">
                <label for="pin">PIN de acceso</label>
                <input type="password" id="pin" name="pin" inputmode="numeric" autocomplete="off" maxlength="6" autofocus required>
            </div>
            <button type="submit" class="btn btn--lleno btn--ancho">Ingresar</button>
        </form>

        <div class="demo-pins">
            PINs de prueba (usuarios cargados en la base de datos):<br>
            1111 &middot; Administrador<br>
            2222 &middot; Cajero<br>
            3333 &middot; Despachador
        </div>
    </div>
</div>
</body>
</html>
