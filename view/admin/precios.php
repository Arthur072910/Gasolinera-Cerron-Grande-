<?php
require_once __DIR__ . '/../../controller/PrecioController.php';

$tituloPagina    = 'Precios por galon';
$subtituloPagina = 'Actualizacion centralizada hacia todas las pantallas de pista';
$vistaActiva     = 'precios';
require __DIR__ . '/../layout/header.php';

$precios   = PrecioController::precios();
$historial = PrecioController::historialPrecios();
?>
<link rel="stylesheet" href="assets/css/precios.css">

<div class="pr-page">
    <span class="pr-page__esquina pr-page__esquina--tl"></span>
    <span class="pr-page__esquina pr-page__esquina--tr"></span>
    <span class="pr-page__esquina pr-page__esquina--bl"></span>
    <span class="pr-page__esquina pr-page__esquina--br"></span>

    <div class="pr-cabecera">
        <div>
            <div class="pr-eyebrow">Tarifa por galon</div>
            <h2 class="pr-titulo">Precios vigentes</h2>
        </div>
        <button class="pr-btn pr-btn--primario" type="button" id="pr-btn-nuevo">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Actualizar precio
        </button>
    </div>

    <div class="pr-grilla">
        <?php foreach ($precios as $p): ?>
        <div class="pr-tarifa">
            <div class="pr-tarifa__combustible"><?= htmlspecialchars($p['combustible']) ?></div>
            <div class="pr-tarifa__precio">$<?= number_format($p['precio'], 3) ?><span>/ GAL</span></div>

            <?php if ($p['delta'] === null): ?>
                <div class="pr-tendencia pr-tendencia--nueva">Primer precio registrado</div>
            <?php elseif ($p['delta'] > 0): ?>
                <div class="pr-tendencia pr-tendencia--sube">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg>
                    +$<?= number_format($p['delta'], 3) ?> vs. anterior
                </div>
            <?php elseif ($p['delta'] < 0): ?>
                <div class="pr-tendencia pr-tendencia--baja">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    -$<?= number_format(abs($p['delta']), 3) ?> vs. anterior
                </div>
            <?php else: ?>
                <div class="pr-tendencia pr-tendencia--igual">Sin cambio vs. anterior</div>
            <?php endif; ?>

            <div class="pr-tarifa__desde">Vigente desde <?= htmlspecialchars($p['vigente_desde']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="pr-nota">
        Al confirmar un precio nuevo, el anterior se cierra automaticamente
        (<code>fecha_fin_vigencia</code>) y queda registrado en el historial de auditoria.
    </p>

    <div class="pr-historial">
        <div class="pr-historial__titulo">Historial de precios (auditoria)</div>
        <div class="pr-tabla-wrap">
            <table class="pr-tabla">
                <thead><tr><th>Combustible</th><th>Precio</th><th>Desde</th><th>Hasta</th><th>Registrado por</th></tr></thead>
                <tbody>
                <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['combustible']) ?></td>
                        <td>$<?= number_format($h['precio'], 3) ?></td>
                        <td><?= htmlspecialchars($h['desde']) ?></td>
                        <td><?= $h['hasta'] === '--' ? '<span class="pr-vigente">Vigente</span>' : htmlspecialchars($h['hasta']) ?></td>
                        <td><?= htmlspecialchars($h['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="pr-modal-overlay" id="pr-modal-overlay" hidden>
    <div class="pr-modal" role="dialog" aria-modal="true" aria-labelledby="pr-modal-titulo">
        <div class="pr-modal__barra"></div>
        <button class="pr-modal__cerrar" type="button" id="pr-modal-cerrar" aria-label="Cerrar">&times;</button>

        <div class="pr-modal__eyebrow">Precios &middot; tarifa de pista</div>
        <div class="pr-modal__titulo" id="pr-modal-titulo">Nuevo precio vigente</div>

        <form method="post" action="index.php?accion=precio_nuevo">
            <div class="pr-campo">
                <label for="pr_combustible">Combustible</label>
                <select name="combustible" id="pr_combustible">
                    <option value="Super">Super</option>
                    <option value="Regular">Regular</option>
                    <option value="Diesel">Diesel</option>
                </select>
            </div>
            <div class="pr-campo">
                <label for="pr_precio">Nuevo precio por galon</label>
                <input type="text" name="precio" id="pr_precio" inputmode="decimal" placeholder="0.000" required>
            </div>
            <p class="pr-ayuda">
                Al confirmar, el precio anterior se cerrara automaticamente y este quedara activo desde hoy.
            </p>
            <div class="pr-modal__acciones">
                <button type="button" class="pr-btn" id="pr-modal-cancelar">Cancelar</button>
                <button type="submit" class="pr-btn pr-btn--primario">Confirmar nuevo precio</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/precios.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
