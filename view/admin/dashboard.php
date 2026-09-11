<?php
require_once __DIR__ . '/../../controller/ReporteController.php';
require_once __DIR__ . '/../../controller/AsistenciaController.php';

$tituloPagina    = 'Panel general';
$subtituloPagina = 'Resumen de operacion del dia';
require __DIR__ . '/../layout/header.php';

$resumen    = ReporteController::resumen();
$asistencia = array_slice(AsistenciaController::registros(), 0, 8);
?>

<div class="chips" style="margin-bottom:24px;">
    <a class="chip" href="index.php?vista=tanques">Tanques (Arduino)</a>
    <a class="chip" href="index.php?vista=precios">Precios</a>
    <a class="chip" href="index.php?vista=inventario">Inventario y tienda</a>
    <a class="chip" href="index.php?vista=proveedores">Proveedores y cisternas</a>
    <a class="chip" href="index.php?vista=asistencia">Asistencia</a>
    <a class="chip" href="index.php?vista=reportes">Reportes</a>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Resumen del dia</span>
        </div>

        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta">Ventas del dia</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor">$<?= number_format($resumen['ventas_hoy'], 2) ?></span>
        </div>
        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta">Galones despachados</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor"><?= number_format($resumen['galones_despachados'], 1) ?> gal</span>
        </div>
        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta">Productos vendidos en tienda</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor"><?= $resumen['productos_vendidos'] ?></span>
        </div>
        <div class="linea-ticket linea-ticket--total">
            <span class="linea-ticket__etiqueta">Turnos cerrados hoy</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor"><?= $resumen['turnos_cerrados'] ?></span>
        </div>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Asistencia registrada hoy</span>
            <a class="btn" href="index.php?vista=asistencia">Ver bitacora completa &rarr;</a>
        </div>
        <table class="bitacora">
            <thead>
                <tr><th>Usuario</th><th>Entrada</th><th>Salida</th></tr>
            </thead>
            <tbody>
                <?php foreach ($asistencia as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['usuario']) ?></td>
                    <td><?= htmlspecialchars($r['entrada']) ?></td>
                    <td><?= htmlspecialchars($r['salida']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
