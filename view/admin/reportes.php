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
<link rel="stylesheet" href="assets/css/reportes.css">

<div class="rp-page">
    <span class="rp-page__esquina rp-page__esquina--tl"></span>
    <span class="rp-page__esquina rp-page__esquina--tr"></span>
    <span class="rp-page__esquina rp-page__esquina--bl"></span>
    <span class="rp-page__esquina rp-page__esquina--br"></span>

    <div class="rp-cabecera">
        <div>
            <div class="rp-eyebrow">Reportes gerenciales</div>
            <h2 class="rp-titulo">Resumen del dia</h2>
        </div>
        <div class="rp-exportar">
            <a class="rp-btn rp-btn--primario" href="index.php?accion=reporte_pdf" id="rp-btn-pdf" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                Exportar PDF
            </a>
            <a class="rp-btn" href="index.php?accion=reporte_csv" id="rp-btn-csv">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                Exportar CSV (Excel)
            </a>
        </div>
    </div>

    <div class="rp-stats">
        <div class="rp-stat">
            <div class="rp-stat__valor">$<?= number_format($resumen['ventas_hoy'], 2) ?></div>
            <div class="rp-stat__etiqueta">Ventas totales</div>
        </div>
        <div class="rp-stat">
            <div class="rp-stat__valor"><?= number_format($resumen['galones_despachados'], 1) ?></div>
            <div class="rp-stat__etiqueta">Galones despachados</div>
        </div>
        <div class="rp-stat">
            <div class="rp-stat__valor"><?= (int) $resumen['productos_vendidos'] ?></div>
            <div class="rp-stat__etiqueta">Productos vendidos</div>
        </div>
        <div class="rp-stat">
            <div class="rp-stat__valor"><?= (int) $resumen['turnos_cerrados'] ?></div>
            <div class="rp-stat__etiqueta">Turnos cerrados</div>
        </div>
    </div>

    <div class="rp-bloque">
        <div class="rp-bloque__titulo">Ventas por tipo de combustible</div>
        <div class="rp-tabla-wrap">
            <table class="rp-tabla">
                <thead><tr><th>Combustible</th><th>Galones</th><th>Total</th></tr></thead>
                <tbody>
                <?php if (empty($ventasCombustible)): ?>
                    <tr><td colspan="3" class="rp-vacio">Sin ventas de combustible registradas hoy.</td></tr>
                <?php endif; ?>
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

    <div class="rp-bloque">
        <div class="rp-bloque__titulo">Productos de tienda mas vendidos</div>
        <div class="rp-tabla-wrap">
            <table class="rp-tabla">
                <thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead>
                <tbody>
                <?php if (empty($topProductos)): ?>
                    <tr><td colspan="3" class="rp-vacio">Sin ventas de tienda registradas hoy.</td></tr>
                <?php endif; ?>
                <?php foreach ($topProductos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['producto']) ?></td>
                        <td><?= $p['unidades'] ?></td>
                        <td>$<?= number_format($p['total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="rp-nota">
            Enlaces relacionados: <a href="index.php?vista=inventario">inventario y kardex</a> &middot;
            <a href="index.php?vista=proveedores">recepciones de cisterna</a>.
        </p>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/reportes.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
