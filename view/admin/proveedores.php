<?php
require_once __DIR__ . '/../../controller/ProveedorController.php';
require_once __DIR__ . '/../../controller/TanqueController.php';

$tituloPagina    = 'Proveedores y cisternas';
$subtituloPagina = 'Registro de compras y auditoria de recepcion';
$vistaActiva     = 'proveedores';
require __DIR__ . '/../layout/header.php';

$proveedores = ProveedorController::proveedores();
$recepciones = ProveedorController::recepciones();
$tanques     = TanqueController::estadoTanques();
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Registrar recepcion de cisterna</span>
            <button class="btn" type="button" id="btn-mostrar-form-recepcion">+ Nueva recepcion</button>
        </div>

        <div class="bloque-paso" id="form-recepcion" data-oculto="1">
            <div class="bloque-paso__titulo">Datos de la factura y descarga</div>
            <form method="post" action="index.php?accion=recepcion_nueva">
                <div class="form-grid">
                    <div class="campo">
                        <label for="r_proveedor">Proveedor</label>
                        <select name="id_proveedor" id="r_proveedor">
                            <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="r_tanque">Tanque destino</label>
                        <select name="id_tanque" id="r_tanque">
                            <?php foreach ($tanques as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= htmlspecialchars($t['combustible']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="r_factura">Numero de factura</label>
                        <input type="text" name="numero_factura" id="r_factura" placeholder="F-0001234" required>
                    </div>
                    <div class="campo">
                        <label for="r_costo">Costo total</label>
                        <input type="text" name="costo_total" id="r_costo" inputmode="decimal" placeholder="0.00" required>
                    </div>
                    <div class="campo">
                        <label for="r_facturados">Galones facturados</label>
                        <input type="text" name="galones_facturados" id="r_facturados" inputmode="decimal" placeholder="0" required>
                    </div>
                    <div class="campo">
                        <label for="r_medidos">Galones medidos en tanque</label>
                        <input type="text" name="galones_medidos" id="r_medidos" inputmode="decimal" placeholder="0" required>
                    </div>
                </div>

                <div class="linea-ticket">
                    <span class="linea-ticket__etiqueta">Diferencia (facturado - medido)</span>
                    <span class="linea-ticket__relleno"></span>
                    <span class="linea-ticket__valor" id="r_diferencia">0.0 gal</span>
                </div>
                <p id="r_diferencia_aviso" style="font-size:0.8rem; color:var(--c-muted);">
                    Ingresa ambos valores para validar la entrega del proveedor.
                </p>

                <button type="submit" class="btn btn--lleno">Confirmar recepcion</button>
            </form>
        </div>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Historial de recepciones</span></div>
        <table class="bitacora">
            <thead><tr><th>Proveedor</th><th>Combustible</th><th>Facturado</th><th>Medido</th><th>Diferencia</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($recepciones as $r):
                $diferencia = $r['galones_facturados'] - $r['galones_medidos'];
                $claseBadge = abs($diferencia) > 10 ? 'badge--alerta' : 'badge--ok';
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['proveedor']) ?></td>
                    <td><?= htmlspecialchars($r['combustible']) ?></td>
                    <td><?= number_format($r['galones_facturados']) ?> gal</td>
                    <td><?= number_format($r['galones_medidos']) ?> gal</td>
                    <td><span class="badge <?= $claseBadge ?>"><?= $diferencia >= 0 ? '-' : '+' ?><?= abs($diferencia) ?> gal</span></td>
                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Proveedores registrados</span>
            <button class="btn" type="button" id="btn-mostrar-form-proveedor">+ Nuevo proveedor</button>
        </div>

        <div class="bloque-paso" id="form-proveedor" data-oculto="1">
            <form method="post" action="index.php?accion=proveedor_nuevo">
                <div class="form-grid">
                    <div class="campo">
                        <label for="pv_nombre">Nombre de la empresa</label>
                        <input type="text" name="nombre" id="pv_nombre" required>
                    </div>
                    <div class="campo">
                        <label for="pv_registro">Registro fiscal (NRC/NIT)</label>
                        <input type="text" name="registro_fiscal" id="pv_registro" required>
                    </div>
                    <div class="campo">
                        <label for="pv_telefono">Telefono</label>
                        <input type="text" name="telefono" id="pv_telefono">
                    </div>
                </div>
                <button type="submit" class="btn btn--lleno">Guardar proveedor</button>
            </form>
        </div>

        <table class="bitacora">
            <thead><tr><th>Nombre</th></tr></thead>
            <tbody>
            <?php foreach ($proveedores as $p): ?>
                <tr><td><?= htmlspecialchars($p['nombre']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/formularios.js"></script>
<script>
(function () {
    const facturados = document.getElementById('r_facturados');
    const medidos = document.getElementById('r_medidos');
    const salida = document.getElementById('r_diferencia');
    const aviso = document.getElementById('r_diferencia_aviso');

    function calcular() {
        const f = parseFloat(facturados.value) || 0;
        const m = parseFloat(medidos.value) || 0;
        const diferencia = f - m;
        salida.textContent = `${diferencia.toFixed(1)} gal`;
        aviso.textContent = Math.abs(diferencia) > 10
            ? 'Diferencia alta: revisar la entrega antes de confirmar.'
            : 'Diferencia dentro de un rango normal.';
    }

    facturados.addEventListener('input', calcular);
    medidos.addEventListener('input', calcular);
})();
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
