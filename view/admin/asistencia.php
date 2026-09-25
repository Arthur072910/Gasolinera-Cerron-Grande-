<?php
require_once __DIR__ . '/../../controller/AsistenciaController.php';

$tituloPagina    = 'Asistencia';
$subtituloPagina = 'Registro automatico de entrada y salida por PIN';
$vistaActiva     = 'asistencia';
require __DIR__ . '/../layout/header.php';

$registros = AsistenciaController::registros();

$hoy = date('Y-m-d');
$totalRegistros = count($registros);
$enCurso        = 0;
$hoyCount       = 0;
foreach ($registros as $r) {
    if ($r['duracion'] === null) { $enCurso++; }
    if (substr($r['entrada'], 0, 10) === $hoy) { $hoyCount++; }
}

$claseRol = [
    'Administrador' => 'as-rolchip--administrador',
    'Cajero'        => 'as-rolchip--cajero',
    'Despachador'   => 'as-rolchip--despachador',
];
?>
<link rel="stylesheet" href="assets/css/asistencia.css">

<div class="as-page">
    <span class="as-page__esquina as-page__esquina--tl"></span>
    <span class="as-page__esquina as-page__esquina--tr"></span>
    <span class="as-page__esquina as-page__esquina--bl"></span>
    <span class="as-page__esquina as-page__esquina--br"></span>

    <div class="as-cabecera">
        <div>
            <div class="as-eyebrow">Control automatizado</div>
            <h2 class="as-titulo">Bitacora de asistencia</h2>
        </div>
    </div>

    <p class="as-nota">
        Cada vez que un Cajero o Despachador inicia sesion con su PIN, el sistema guarda
        automaticamente la marca de tiempo en <code>asistencia</code>; al cerrar sesion se
        registra la salida. No requiere que nadie lo anote a mano.
    </p>

    <div class="as-stats">
        <div class="as-stat">
            <div class="as-stat__valor"><?= $totalRegistros ?></div>
            <div class="as-stat__etiqueta">Registros totales</div>
        </div>
        <div class="as-stat as-stat--curso">
            <div class="as-stat__valor"><?= $enCurso ?></div>
            <div class="as-stat__etiqueta">En curso ahora</div>
        </div>
        <div class="as-stat">
            <div class="as-stat__valor"><?= $hoyCount ?></div>
            <div class="as-stat__etiqueta">Registrados hoy</div>
        </div>
    </div>

    <div class="as-toolbar">
        <div class="as-buscador-wrap">
            <svg class="as-toolbar__icono" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" class="as-buscador" id="as-buscar" placeholder="Buscar por nombre...">
        </div>
        <div class="as-chips" id="as-filtro-rol">
            <button class="as-chip as-chip--activo" type="button" data-rol="todos">Todos</button>
            <button class="as-chip" type="button" data-rol="Administrador">Administrador</button>
            <button class="as-chip" type="button" data-rol="Cajero">Cajero</button>
            <button class="as-chip" type="button" data-rol="Despachador">Despachador</button>
        </div>
    </div>

    <div class="as-tabla-wrap">
        <table class="as-tabla">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Entrada</th><th>Salida</th><th>Duracion</th></tr></thead>
            <tbody id="as-tbody">
            <?php foreach ($registros as $r): ?>
                <tr data-nombre="<?= htmlspecialchars(mb_strtolower($r['usuario'])) ?>" data-rol="<?= htmlspecialchars($r['rol']) ?>">
                    <td><?= htmlspecialchars($r['usuario']) ?></td>
                    <td><span class="as-rolchip <?= $claseRol[$r['rol']] ?? '' ?>"><?= htmlspecialchars($r['rol']) ?></span></td>
                    <td><?= htmlspecialchars($r['entrada']) ?></td>
                    <td>
                        <?php if ($r['duracion'] === null): ?>
                            <span class="as-en-curso">En curso</span>
                        <?php else: ?>
                            <?= htmlspecialchars($r['salida']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['duracion'] === null ? '&mdash;' : htmlspecialchars($r['duracion']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="as-vacio" id="as-vacio" <?= empty($registros) ? '' : 'hidden' ?>>
            <?= empty($registros) ? 'Todavia no hay registros de asistencia.' : 'No se encontraron registros con ese criterio.' ?>
        </p>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/asistencia.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
