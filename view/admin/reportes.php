<?php
require_once __DIR__ . '/../../controller/ReporteController.php';

$tituloPagina    = 'Reportes';
$subtituloPagina = 'Ventas por combustible, productos y exportacion';
$vistaActiva     = 'reportes';
require __DIR__ . '/../layout/header.php';

$resumen           = ReporteController::resumen();
$ventasCombustible = ReporteController::ventasPorCombustible();
$topProductos      = ReporteController::topProductos();
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Resumen del dia</span>
            <div>
                <button class="btn" type="button" disabled>Exportar PDF</button>
                <button class="btn" type="button" disabled>Exportar Excel</button>
            </div>
        </div>
        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta">Ventas totales</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor">$<?= number_format($resumen['ventas_hoy'], 2) ?></span>
        </div>
        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta">Galones despachados</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor"><?= number_format($resumen['galones_despachados'], 1) ?> gal</span>
        </div>
        <div class="linea-ticket linea-ticket--total">
            <span class="linea-ticket__etiqueta">Turnos cerrados</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor"><?= $resumen['turnos_cerrados'] ?></span>
        </div>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Ventas por tipo de combustible</span></div>
        <table class="bitacora">
            <thead><tr><th>Combustible</th><th>Galones</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($ventasCombustible as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['combustible']) ?></td>
                    <td><?= number_format($v['galones'], 1) ?> gal</td>
                    <td>$<?= number_format($v['total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Productos de tienda mas vendidos</span></div>
        <table class="bitacora">
            <thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($topProductos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['producto']) ?></td>
                    <td><?= $p['unidades'] ?></td>
                    <td>$<?= number_format($p['total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="color:var(--c-muted); font-size:0.78rem; margin-top:12px;">
            Enlaces relacionados: <a href="index.php?vista=inventario">inventario y kardex</a> &middot;
            <a href="index.php?vista=proveedores">recepciones de cisterna</a>.
        </p>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
