<?php
require_once __DIR__ . '/../../controller/UsuarioController.php';

$tituloPagina    = 'Usuarios';
$subtituloPagina = 'Administrador, Cajero y Despachador';
$vistaActiva     = 'usuarios';
require __DIR__ . '/../layout/header.php';

$usuarios   = UsuarioController::usuarios();
$roles      = UsuarioController::roles();
$idUsuarioActual = Sesion::idUsuarioActual();

$totalActivos = 0;
foreach ($usuarios as $u) {
    if ($u['estado'] === 'activo') {
        $totalActivos++;
    }
}

// Si la ultima accion de este formulario termino en error, recuperamos
// lo que el usuario habia escrito para reabrir el modal sin perderlo.
$reabrir = ($flash && $flash['tipo'] === 'error') ? Sesion::leerFlashDatos() : null;

$claseRol = [
    'Administrador' => 'usr-rolchip--administrador',
    'Cajero'        => 'usr-rolchip--cajero',
    'Despachador'   => 'usr-rolchip--despachador',
];

$claseAvatar = [
    'Administrador' => 'usr-avatar--administrador',
    'Cajero'        => 'usr-avatar--cajero',
    'Despachador'   => 'usr-avatar--despachador',
];

function usr_iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/', trim($nombre));
    $iniciales = mb_strtoupper(mb_substr($partes[0], 0, 1));
    if (count($partes) > 1) {
        $iniciales .= mb_strtoupper(mb_substr(end($partes), 0, 1));
    }
    return $iniciales;
}
?>
<link rel="stylesheet" href="assets/css/usuarios.css">

<div class="usr-page">
    <span class="usr-page__esquina usr-page__esquina--tl"></span>
    <span class="usr-page__esquina usr-page__esquina--tr"></span>
    <span class="usr-page__esquina usr-page__esquina--bl"></span>
    <span class="usr-page__esquina usr-page__esquina--br"></span>

    <div class="usr-cabecera">
        <div>
            <div class="usr-eyebrow">Control de acceso</div>
            <h2 class="usr-titulo">Cuentas registradas</h2>
        </div>
        <button class="usr-btn usr-btn--primario" type="button" id="usr-btn-nuevo">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo usuario
        </button>
    </div>

    <div class="usr-stats">
        <div class="usr-stat">
            <div class="usr-stat__valor"><?= count($usuarios) ?></div>
            <div class="usr-stat__etiqueta">Cuentas totales</div>
        </div>
        <div class="usr-stat usr-stat--activo">
            <div class="usr-stat__valor"><?= $totalActivos ?></div>
            <div class="usr-stat__etiqueta">Activas</div>
        </div>
        <div class="usr-stat usr-stat--inactivo">
            <div class="usr-stat__valor"><?= count($usuarios) - $totalActivos ?></div>
            <div class="usr-stat__etiqueta">Inactivas</div>
        </div>
    </div>

    <div class="usr-toolbar">
        <svg class="usr-toolbar__icono" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" class="usr-buscador" id="usr-buscar" placeholder="Buscar por nombre o rol...">
    </div>

    <div class="usr-tabla-wrap">
        <table class="usr-tabla">
            <thead>
                <tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody id="usr-tbody">
            <?php foreach ($usuarios as $u): ?>
                <tr data-nombre="<?= htmlspecialchars(mb_strtolower($u['nombre'])) ?>" data-rol="<?= htmlspecialchars(mb_strtolower($u['nombre_rol'])) ?>">
                    <td>
                        <div class="usr-persona">
                            <span class="usr-avatar <?= $claseAvatar[$u['nombre_rol']] ?? '' ?>"><?= htmlspecialchars(usr_iniciales($u['nombre'])) ?></span>
                            <div>
                                <div class="usr-persona__nombre"><?= htmlspecialchars($u['nombre']) ?></div>
                                <div class="usr-persona__id">ID #<?= $u['id_usuario'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="usr-rolchip <?= $claseRol[$u['nombre_rol']] ?? '' ?>"><?= htmlspecialchars($u['nombre_rol']) ?></span>
                    </td>
                    <td>
                        <?php if ($u['estado'] === 'activo'): ?>
                            <span class="usr-estado usr-estado--activo">Activo</span>
                        <?php else: ?>
                            <span class="usr-estado usr-estado--inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="usr-acciones">
                        <button class="usr-btn usr-btn--pequeno" type="button"
                                data-editar
                                data-id="<?= $u['id_usuario'] ?>"
                                data-nombre="<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>"
                                data-id-rol="<?= $u['id_rol'] ?>">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            Editar
                        </button>

                        <?php if ((int) $u['id_usuario'] === (int) $idUsuarioActual): ?>
                            <span class="usr-actual">(tu sesion actual)</span>
                        <?php else: ?>
                            <form method="post" action="index.php?accion=usuario_estado" class="usr-form-inline"
                                  data-nombre="<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>"
                                  data-nueva-accion="<?= $u['estado'] === 'activo' ? 'desactivar' : 'activar' ?>">
                                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                <input type="hidden" name="estado" value="<?= $u['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                                <?php if ($u['estado'] === 'activo'): ?>
                                    <button class="usr-btn usr-btn--pequeno usr-btn--peligro" type="submit">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                                        Desactivar
                                    </button>
                                <?php else: ?>
                                    <button class="usr-btn usr-btn--pequeno" type="submit">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                                        Activar
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="usr-vacio" id="usr-vacio" hidden>No se encontraron usuarios con ese criterio.</p>
    </div>
</div>

<div class="usr-modal-overlay" id="usr-modal-overlay" hidden
     <?php if ($reabrir): ?>
     data-reabrir-modo="<?= htmlspecialchars($reabrir['modo']) ?>"
     data-reabrir-id="<?= htmlspecialchars($reabrir['id'] ?? '') ?>"
     data-reabrir-nombre="<?= htmlspecialchars($reabrir['nombre'] ?? '', ENT_QUOTES) ?>"
     data-reabrir-id-rol="<?= htmlspecialchars($reabrir['id_rol'] ?? '') ?>"
     <?php endif; ?>>
    <div class="usr-modal" role="dialog" aria-modal="true" aria-labelledby="usr-modal-titulo">
        <div class="usr-modal__barra"></div>
        <button class="usr-modal__cerrar" type="button" id="usr-modal-cerrar" aria-label="Cerrar">&times;</button>

        <div class="usr-modal__eyebrow">Usuarios &middot; acceso al sistema</div>
        <div class="usr-modal__titulo" id="usr-modal-titulo">Nuevo usuario</div>

        <?php if ($reabrir): ?>
        <div class="usr-error"><span>&#9888;</span><span><?= htmlspecialchars($flash['mensaje']) ?></span></div>
        <?php endif; ?>

        <form id="usr-form" method="post" action="index.php?accion=usuario_nuevo">
            <input type="hidden" name="id_usuario" id="usr-f-id">

            <div class="usr-campo">
                <label for="usr-f-nombre">Nombre completo</label>
                <input type="text" name="nombre" id="usr-f-nombre" required>
            </div>

            <div class="usr-campo">
                <label for="usr-f-rol">Rol</label>
                <select name="id_rol" id="usr-f-rol">
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre_rol']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="usr-campo-grupo">
                <div class="usr-campo">
                    <label for="usr-f-pin" id="usr-f-pin-label">PIN de acceso</label>
                    <input type="password" name="pin" id="usr-f-pin" maxlength="6" inputmode="numeric" autocomplete="off">
                </div>
                <div class="usr-campo">
                    <label for="usr-f-pin2">Confirmar PIN</label>
                    <input type="password" name="pin_confirmar" id="usr-f-pin2" maxlength="6" inputmode="numeric" autocomplete="off">
                </div>
            </div>
            <p class="usr-ayuda" id="usr-f-pin-ayuda" hidden>Deja estos campos en blanco para conservar el PIN actual.</p>

            <div class="usr-modal__acciones">
                <button type="button" class="usr-btn" id="usr-modal-cancelar">Cancelar</button>
                <button type="submit" class="usr-btn usr-btn--primario">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/usuarios.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
