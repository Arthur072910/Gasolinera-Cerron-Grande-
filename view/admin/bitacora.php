<?php
require_once __DIR__ . '/../../controller/BitacoraController.php';

$tituloPagina    = 'Bitacora';
$subtituloPagina = 'Actividad de cada usuario en el sistema';
$vistaActiva     = 'bitacora';
require __DIR__ . '/../layout/header.php';

$filtros = [
    'id_usuario' => $_GET['usuario'] ?? '',
    'accion'     => $_GET['accion_filtro'] ?? '',
    'desde'      => $_GET['desde'] ?? '',
    'hasta'      => $_GET['hasta'] ?? '',
];
$filtros = array_filter($filtros, fn ($v) => $v !== '');

$registros = BitacoraController::listar($filtros);
$opciones  = BitacoraController::opcionesFiltro();
$totalHoy  = BitacoraController::totalHoy();
$hayFiltro = !empty($filtros);
?>
<link rel="stylesheet" href="assets/css/bitacora.css">

<div class="bt-page">
    <span class="bt-page__esquina bt-page__esquina--tl"></span>
    <span class="bt-page__esquina bt-page__esquina--tr"></span>
    <span class="bt-page__esquina bt-page__esquina--bl"></span>
    <span class="bt-page__esquina bt-page__esquina--br"></span>

    <div class="bt-cabecera">
        <div>
            <div class="bt-eyebrow">Auditoria</div>
            <h2 class="bt-titulo">Registro de actividad</h2>
        </div>
        <div class="bt-resumen-hoy">
            <span class="bt-resumen-hoy__valor"><?= (int) $totalHoy ?></span>
            <span class="bt-resumen-hoy__etiqueta">Registros hoy</span>
        </div>
    </div>

    <form class="bt-filtros" method="get" action="index.php">
        <input type="hidden" name="vista" value="bitacora">

        <div class="bt-filtro">
            <label for="f-usuario">Usuario</label>
            <select name="usuario" id="f-usuario">
                <option value="">Todos</option>
                <?php foreach ($opciones['usuarios'] as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= (string) ($filtros['id_usuario'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="bt-filtro">
            <label for="f-accion">Accion</label>
            <select name="accion_filtro" id="f-accion">
                <option value="">Todas</option>
                <?php foreach ($opciones['acciones'] as $a): ?>
                <option value="<?= htmlspecialchars($a['id']) ?>" <?= ($filtros['accion'] ?? '') === $a['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="bt-filtro">
            <label for="f-desde">Desde</label>
            <input type="date" name="desde" id="f-desde" value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>">
        </div>

        <div class="bt-filtro">
            <label for="f-hasta">Hasta</label>
            <input type="date" name="hasta" id="f-hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '') ?>">
        </div>

        <div class="bt-filtro-acciones">
            <button class="bt-btn bt-btn--primario" type="submit">Filtrar</button>
            <?php if ($hayFiltro): ?>
            <a class="bt-btn" href="index.php?vista=bitacora">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="bt-tabla-wrap">
        <table class="bt-tabla">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Accion</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr><td colspan="5" class="bt-vacio">
                    <?= $hayFiltro ? 'No hay actividad que coincida con ese filtro.' : 'Todavia no hay actividad registrada.' ?>
                </td></tr>
                <?php else: foreach ($registros as $r): ?>
                <tr>
                    <td class="bt-fecha"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['fecha']))) ?></td>
                    <td><?= htmlspecialchars($r['usuario']) ?></td>
                    <td><span class="bt-rol"><?= htmlspecialchars($r['rol']) ?></span></td>
                    <td><span class="bt-badge"><?= htmlspecialchars($r['accion_texto']) ?></span></td>
                    <td class="bt-detalle"><?= htmlspecialchars($r['descripcion']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($registros) && count($registros) >= 300): ?>
    <p class="bt-nota">Se muestran los 300 registros mas recientes que coinciden con el filtro. Ajusta el rango de fechas para ver un periodo mas especifico.</p>
    <?php endif; ?>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/bitacora.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
