<?php
require_once __DIR__ . '/../../controller/PrecioController.php';

$tituloPagina    = 'Precios por galon';
$subtituloPagina = 'Actualizacion centralizada hacia todas las pantallas de pista';
$vistaActiva     = 'precios';
require __DIR__ . '/../layout/header.php';

$precios   = PrecioController::precios();
$historial = PrecioController::historialPrecios();
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Tarifa vigente</span>
            <button class="btn" type="button" id="btn-mostrar-form-precio">+ Actualizar precio</button>
        </div>

        <div class="bloque-paso" id="form-precio" data-oculto="1">
            <div class="bloque-paso__titulo">Nuevo precio vigente</div>
            <form method="post" action="index.php?accion=precio_nuevo">
                <div class="form-grid">
                    <div class="campo">
                        <label for="pr_combustible">Combustible</label>
                        <select name="combustible" id="pr_combustible">
                            <option value="Super">Super</option>
                            <option value="Regular">Regular</option>
                            <option value="Diesel">Diesel</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="pr_precio">Nuevo precio por galon</label>
                        <input type="text" name="precio" id="pr_precio" inputmode="decimal" placeholder="0.000" required>
                    </div>
                </div>
                <p style="color:var(--c-muted); font-size:0.8rem;">
                    Al confirmar, el precio anterior se cerrara automaticamente
                    (<code>fecha_fin_vigencia</code>) y este quedara activo desde hoy.
                </p>
                <button type="submit" class="btn btn--lleno">Confirmar nuevo precio</button>
            </form>
        </div>

        <table class="bitacora">
            <thead><tr><th>Combustible</th><th>Precio por galon</th><th>Vigente desde</th></tr></thead>
            <tbody>
            <?php foreach ($precios as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['combustible']) ?></td>
                    <td>$<?= number_format($p['precio'], 3) ?></td>
                    <td><?= htmlspecialchars($p['vigente_desde']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Historial de precios (auditoria)</span></div>
        <table class="bitacora">
            <thead><tr><th>Combustible</th><th>Precio</th><th>Desde</th><th>Hasta</th><th>Registrado por</th></tr></thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['combustible']) ?></td>
                    <td>$<?= number_format($h['precio'], 3) ?></td>
                    <td><?= htmlspecialchars($h['desde']) ?></td>
                    <td><?= htmlspecialchars($h['hasta']) ?></td>
                    <td><?= htmlspecialchars($h['usuario']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/formularios.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
