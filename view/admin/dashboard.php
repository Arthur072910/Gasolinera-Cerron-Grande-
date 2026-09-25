<?php
require_once __DIR__ . '/../../controller/ReporteController.php';
require_once __DIR__ . '/../../controller/AsistenciaController.php';
require_once __DIR__ . '/../../controller/TurnoController.php';
require_once __DIR__ . '/../../controller/TanqueController.php';
require_once __DIR__ . '/../../controller/InventarioController.php';

$tituloPagina    = 'Panel general';
$subtituloPagina = 'Resumen de operacion del dia';
require __DIR__ . '/../layout/header.php';

$resumen        = ReporteController::resumen();
$asistencia     = array_slice(AsistenciaController::registros(), 0, 6);
$turnosActivos  = TurnoController::turnosActivos();
$tanques        = TanqueController::estadoTanques();
$alertasStock   = array_slice(InventarioController::alertasStock(), 0, 5);

$etiquetaTanque = [
    'verde'    => ['clase' => 'normal',      'texto' => 'Normal'],
    'amarillo' => ['clase' => 'advertencia', 'texto' => 'Advertencia'],
    'rojo'     => ['clase' => 'alerta',      'texto' => 'Stock minimo'],
];
?>
<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="pg-page">
    <span class="pg-page__esquina pg-page__esquina--tl"></span>
    <span class="pg-page__esquina pg-page__esquina--tr"></span>
    <span class="pg-page__esquina pg-page__esquina--bl"></span>
    <span class="pg-page__esquina pg-page__esquina--br"></span>

    <div class="pg-cabecera">
        <div>
            <div class="pg-eyebrow">Vision general</div>
            <h2 class="pg-titulo">Operacion de hoy</h2>
        </div>
        <nav class="pg-accesos">
            <a href="index.php?vista=tanques">Tanques</a>
            <a href="index.php?vista=precios">Precios</a>
            <a href="index.php?vista=inventario">Inventario</a>
            <a href="index.php?vista=proveedores">Proveedores</a>
            <a href="index.php?vista=asistencia">Asistencia</a>
            <a href="index.php?vista=reportes">Reportes</a>
        </nav>
    </div>

    <div class="pg-stats">
        <div class="pg-stat">
            <div class="pg-stat__valor">$<?= number_format($resumen['ventas_hoy'], 2) ?></div>
            <div class="pg-stat__etiqueta">Ventas del dia</div>
        </div>
        <div class="pg-stat">
            <div class="pg-stat__valor"><?= number_format($resumen['galones_despachados'], 1) ?></div>
            <div class="pg-stat__etiqueta">Galones despachados</div>
        </div>
        <div class="pg-stat">
            <div class="pg-stat__valor"><?= (int) $resumen['productos_vendidos'] ?></div>
            <div class="pg-stat__etiqueta">Productos vendidos</div>
        </div>
        <div class="pg-stat">
            <div class="pg-stat__valor"><?= (int) $resumen['turnos_cerrados'] ?></div>
            <div class="pg-stat__etiqueta">Turnos cerrados hoy</div>
        </div>
    </div>

    <div class="pg-columnas">
        <div class="pg-bloque">
            <div class="pg-bloque__titulo">
                <span>Turnos activos ahora</span>
                <span class="pg-contador"><?= count($turnosActivos) ?></span>
            </div>
            <?php if (empty($turnosActivos)): ?>
                <p class="pg-vacio">No hay turnos abiertos en este momento.</p>
            <?php else: ?>
                <div class="pg-lista">
                    <?php foreach ($turnosActivos as $t): ?>
                    <div class="pg-fila-turno">
                        <div>
                            <div class="pg-fila-turno__usuario"><?= htmlspecialchars($t['usuario']) ?></div>
                            <div class="pg-fila-turno__meta"><?= htmlspecialchars($t['rol']) ?> &middot; caja <?= htmlspecialchars($t['tipo_caja']) ?></div>
                        </div>
                        <div class="pg-fila-turno__hora"><?= htmlspecialchars($t['hora_inicio']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="pg-bloque">
            <div class="pg-bloque__titulo">
                <span>Tanques</span>
                <a class="pg-enlace" href="index.php?vista=tanques">Ver monitoreo &rarr;</a>
            </div>
            <div class="pg-lista">
                <?php foreach ($tanques as $t):
                    $porcentaje = $t['capacidad'] > 0 ? round(($t['nivel_actual'] / $t['capacidad']) * 100) : 0;
                    $est = $etiquetaTanque[$t['estado_led']];
                ?>
                <div class="pg-fila-tanque">
                    <div class="pg-fila-tanque__info">
                        <div class="pg-fila-tanque__nombre"><?= htmlspecialchars($t['nombre']) ?></div>
                        <div class="pg-fila-tanque__meta"><?= htmlspecialchars($t['combustible']) ?> &middot; <?= $porcentaje ?>%</div>
                    </div>
                    <span class="pg-badge pg-badge--<?= $est['clase'] ?>"><?= $est['texto'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="pg-bloque pg-bloque--ancho">
        <div class="pg-bloque__titulo">
            <span>Alertas de inventario</span>
            <a class="pg-enlace" href="index.php?vista=inventario">Ver inventario &rarr;</a>
        </div>
        <?php if (empty($alertasStock)): ?>
            <p class="pg-vacio">Todos los productos tienen stock por encima del minimo.</p>
        <?php else: ?>
            <div class="pg-tabla-wrap">
                <table class="pg-tabla">
                    <thead><tr><th>Producto</th><th>Stock actual</th><th>Stock minimo</th></tr></thead>
                    <tbody>
                    <?php foreach ($alertasStock as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['producto']) ?></td>
                            <td><?= (int) $a['stock_actual'] ?></td>
                            <td><?= (int) $a['stock_minimo'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="pg-bloque pg-bloque--ancho">
        <div class="pg-bloque__titulo">
            <span>Asistencia registrada hoy</span>
            <a class="pg-enlace" href="index.php?vista=asistencia">Ver bitacora completa &rarr;</a>
        </div>
        <div class="pg-tabla-wrap">
            <table class="pg-tabla">
                <thead><tr><th>Usuario</th><th>Entrada</th><th>Salida</th></tr></thead>
                <tbody>
                <?php foreach ($asistencia as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['usuario']) ?></td>
                        <td><?= htmlspecialchars($r['entrada']) ?></td>
                        <td><?= $r['salida'] === '--' ? '<span class="pg-en-turno">En turno</span>' : htmlspecialchars($r['salida']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/dashboard.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
