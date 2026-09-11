<?php
require_once __DIR__ . '/../../controller/UsuarioController.php';

$tituloPagina    = 'Usuarios';
$subtituloPagina = 'Administrador, Cajero y Despachador';
$vistaActiva     = 'usuarios';
require __DIR__ . '/../layout/header.php';

$usuarios = UsuarioController::usuarios();
$roles    = UsuarioController::roles();
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Usuarios registrados</span>
            <button class="btn btn--lleno" type="button" id="btn-mostrar-form-usuario">+ Nuevo usuario</button>
        </div>

        <div class="bloque-paso" id="form-usuario" data-oculto="1">
            <div class="bloque-paso__titulo">Registrar nuevo usuario</div>
            <form method="post" action="index.php?accion=usuario_nuevo">
                <div class="form-grid">
                    <div class="campo">
                        <label for="u_nombre">Nombre completo</label>
                        <input type="text" name="nombre" id="u_nombre" required>
                    </div>
                    <div class="campo">
                        <label for="u_rol">Rol</label>
                        <select name="id_rol" id="u_rol">
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="u_pin">PIN de acceso (4 a 6 digitos)</label>
                        <input type="password" name="pin" id="u_pin" maxlength="6" inputmode="numeric" required>
                    </div>
                    <div class="campo">
                        <label for="u_pin_confirmar">Confirmar PIN</label>
                        <input type="password" name="pin_confirmar" id="u_pin_confirmar" maxlength="6" inputmode="numeric" required>
                    </div>
                </div>
                <button type="submit" class="btn btn--lleno">Guardar usuario</button>
            </form>
        </div>

        <table class="bitacora">
            <thead><tr><th>Nombre</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['nombre_rol']) ?></td>
                    <td>
                        <?php if ($u['estado'] === 'activo'): ?>
                            <span class="estado estado--normal"><span class="estado__marca"></span> Activo</span>
                        <?php else: ?>
                            <span class="estado estado--alerta"><span class="estado__marca"></span> Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <form method="post" action="index.php?accion=usuario_estado" style="display:inline;">
                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                            <input type="hidden" name="estado" value="<?= $u['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                            <button class="btn" type="submit"><?= $u['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/formularios.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
