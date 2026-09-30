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

$totalDiferenciasAltas = 0;
foreach ($recepciones as $r) {
    if (abs($r['galones_facturados'] - $r['galones_medidos']) > 10) { $totalDiferenciasAltas++; }
}

// Si la ultima accion de este formulario termino en error, recuperamos
// lo que el usuario habia escrito para reabrir el modal sin perderlo.
$reabrir = ($flash && $flash['tipo'] === 'error') ? Sesion::leerFlashDatos() : null;
?>
<link rel="stylesheet" href="assets/css/proveedores.css">

<div class="pv-page">
    <span class="pv-page__esquina pv-page__esquina--tl"></span>
    <span class="pv-page__esquina pv-page__esquina--tr"></span>
    <span class="pv-page__esquina pv-page__esquina--bl"></span>
    <span class="pv-page__esquina pv-page__esquina--br"></span>

    <div class="pv-cabecera">
        <div>
            <div class="pv-eyebrow">Cadena de suministro</div>
            <h2 class="pv-titulo">Proveedores y cisternas</h2>
        </div>
        <button class="pv-btn pv-btn--primario" type="button" id="pv-btn-recepcion">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nueva recepcion
        </button>
    </div>

    <div class="pv-stats">
        <div class="pv-stat">
            <div class="pv-stat__valor"><?= count($proveedores) ?></div>
            <div class="pv-stat__etiqueta">Proveedores</div>
        </div>
        <div class="pv-stat">
            <div class="pv-stat__valor"><?= count($recepciones) ?></div>
            <div class="pv-stat__etiqueta">Recepciones registradas</div>
        </div>
        <div class="pv-stat <?= $totalDiferenciasAltas > 0 ? 'pv-stat--alerta' : '' ?>">
            <div class="pv-stat__valor"><?= $totalDiferenciasAltas ?></div>
            <div class="pv-stat__etiqueta">Diferencias altas (&gt;10 gal)</div>
        </div>
    </div>

    <div class="pv-bloque">
        <div class="pv-bloque__titulo">Historial de recepciones</div>
        <div class="pv-tabla-wrap">
            <table class="pv-tabla">
                <thead><tr><th>Proveedor</th><th>Combustible</th><th>Facturado</th><th>Medido</th><th>Diferencia</th><th>Fecha</th></tr></thead>
                <tbody>
                <?php if (empty($recepciones)): ?>
                    <tr><td colspan="6" class="pv-vacio-fila">Todavia no hay recepciones registradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($recepciones as $r):
                    $diferencia = $r['galones_facturados'] - $r['galones_medidos'];
                    $claseDif   = abs($diferencia) > 10 ? 'pv-dif--alta' : 'pv-dif--ok';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($r['proveedor']) ?></td>
                        <td><span class="pv-catchip"><?= htmlspecialchars($r['combustible']) ?></span></td>
                        <td><?= number_format($r['galones_facturados']) ?> gal</td>
                        <td><?= number_format($r['galones_medidos']) ?> gal</td>
                        <td><span class="pv-dif <?= $claseDif ?>"><?= $diferencia >= 0 ? '-' : '+' ?><?= number_format(abs($diferencia), 1) ?> gal</span></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pv-bloque">
        <div class="pv-bloque__cabecera">
            <div class="pv-bloque__titulo">Proveedores registrados</div>
            <button class="pv-btn" type="button" id="pv-btn-nuevo">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo proveedor
            </button>
        </div>

        <div class="pv-toolbar">
            <svg class="pv-toolbar__icono" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" class="pv-buscador" id="pv-buscar" placeholder="Buscar por nombre...">
        </div>

        <div class="pv-tabla-wrap">
            <table class="pv-tabla">
                <thead><tr><th>Nombre</th><th>Registro fiscal</th><th>Telefono</th><th>Acciones</th></tr></thead>
                <tbody id="pv-tbody">
                <?php foreach ($proveedores as $p): ?>
                    <tr data-nombre="<?= htmlspecialchars(mb_strtolower($p['nombre'])) ?>">
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['registro_fiscal']) ?></td>
                        <td><?= $p['telefono'] ? htmlspecialchars($p['telefono']) : '<span class="pv-sin-dato">—</span>' ?></td>
                        <td class="pv-acciones">
                            <button class="pv-icon-btn" type="button" title="Editar proveedor"
                                    data-editar
                                    data-id="<?= $p['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                    data-registro="<?= htmlspecialchars($p['registro_fiscal'], ENT_QUOTES) ?>"
                                    data-telefono="<?= htmlspecialchars((string) $p['telefono'], ENT_QUOTES) ?>">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </button>
                            <form method="post" action="index.php?accion=proveedor_eliminar" class="pv-form-inline"
                                  data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
                                <input type="hidden" name="id_proveedor" value="<?= $p['id'] ?>">
                                <button class="pv-icon-btn pv-icon-btn--peligro" type="submit" title="Eliminar proveedor">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="pv-vacio" id="pv-vacio" hidden>No se encontraron proveedores con ese nombre.</p>
        </div>
    </div>
</div>

<!-- Modal: crear/editar proveedor -->
<div class="pv-modal-overlay" id="pv-modal-overlay" hidden
     <?php if ($reabrir && in_array($reabrir['modo'], ['crear', 'editar'], true)): ?>
     data-reabrir-modo="<?= htmlspecialchars($reabrir['modo']) ?>"
     data-reabrir-id="<?= htmlspecialchars($reabrir['id'] ?? '') ?>"
     data-reabrir-nombre="<?= htmlspecialchars($reabrir['nombre'] ?? '', ENT_QUOTES) ?>"
     data-reabrir-registro="<?= htmlspecialchars($reabrir['registro_fiscal'] ?? '', ENT_QUOTES) ?>"
     data-reabrir-telefono="<?= htmlspecialchars($reabrir['telefono'] ?? '', ENT_QUOTES) ?>"
     <?php endif; ?>>
    <div class="pv-modal" role="dialog" aria-modal="true" aria-labelledby="pv-modal-titulo">
        <div class="pv-modal__barra"></div>
        <button class="pv-modal__cerrar" type="button" id="pv-modal-cerrar" aria-label="Cerrar">&times;</button>

        <div class="pv-modal__eyebrow">Proveedores</div>
        <div class="pv-modal__titulo" id="pv-modal-titulo">Nuevo proveedor</div>

        <?php if ($reabrir && in_array($reabrir['modo'], ['crear', 'editar'], true)): ?>
        <div class="pv-error"><span>&#9888;</span><span><?= htmlspecialchars($flash['mensaje']) ?></span></div>
        <?php endif; ?>

        <form id="pv-form" method="post" action="index.php?accion=proveedor_nuevo">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
            <input type="hidden" name="id_proveedor" id="pv-f-id">
            <div class="pv-campo">
                <label for="pv-f-nombre">Nombre de la empresa</label>
                <input type="text" name="nombre" id="pv-f-nombre" required>
            </div>
            <div class="pv-campo">
                <label for="pv-f-registro">Registro fiscal (NRC/NIT)</label>
                <input type="text" name="registro_fiscal" id="pv-f-registro" required>
            </div>
            <div class="pv-campo">
                <label for="pv-f-telefono">Telefono</label>
                <input type="text" name="telefono" id="pv-f-telefono">
            </div>
            <div class="pv-modal__acciones">
                <button type="button" class="pv-btn" id="pv-modal-cancelar">Cancelar</button>
                <button type="submit" class="pv-btn pv-btn--primario">Guardar proveedor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: nueva recepcion -->
<div class="pv-modal-overlay" id="pv-recepcion-overlay" hidden
     <?php if ($reabrir && $reabrir['modo'] === 'recepcion'): ?>
     data-reabrir="1"
     data-reabrir-proveedor="<?= htmlspecialchars($reabrir['id_proveedor'] ?? '') ?>"
     data-reabrir-tanque="<?= htmlspecialchars($reabrir['id_tanque'] ?? '') ?>"
     data-reabrir-factura="<?= htmlspecialchars($reabrir['numero_factura'] ?? '', ENT_QUOTES) ?>"
     data-reabrir-costo="<?= htmlspecialchars($reabrir['costo_total'] ?? '') ?>"
     data-reabrir-facturados="<?= htmlspecialchars($reabrir['galones_facturados'] ?? '') ?>"
     data-reabrir-medidos="<?= htmlspecialchars($reabrir['galones_medidos'] ?? '') ?>"
     data-reabrir-nivel="<?= htmlspecialchars($reabrir['nivel_cm'] ?? '') ?>"
     <?php endif; ?>>
    <div class="pv-modal" role="dialog" aria-modal="true" aria-labelledby="pv-recepcion-titulo">
        <div class="pv-modal__barra"></div>
        <button class="pv-modal__cerrar" type="button" id="pv-recepcion-cerrar" aria-label="Cerrar">&times;</button>

        <div class="pv-modal__eyebrow">Recepcion de cisterna</div>
        <div class="pv-modal__titulo" id="pv-recepcion-titulo">Datos de la factura y descarga</div>

        <?php if ($reabrir && $reabrir['modo'] === 'recepcion'): ?>
        <div class="pv-error"><span>&#9888;</span><span><?= htmlspecialchars($flash['mensaje']) ?></span></div>
        <?php endif; ?>

        <form method="post" action="index.php?accion=recepcion_nueva">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
            <div class="pv-campo-grupo">
                <div class="pv-campo">
                    <label for="r_proveedor">Proveedor</label>
                    <select name="id_proveedor" id="r_proveedor">
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pv-campo">
                    <label for="r_tanque">Tanque destino</label>
                    <select name="id_tanque" id="r_tanque">
                        <?php foreach ($tanques as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= htmlspecialchars($t['combustible']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pv-campo">
                <label for="r_factura">Numero de factura</label>
                <input type="text" name="numero_factura" id="r_factura" placeholder="F-0001234" required>
            </div>

            <div class="pv-campo-grupo">
                <div class="pv-campo">
                    <label for="r_costo">Costo total</label>
                    <input type="text" name="costo_total" id="r_costo" inputmode="decimal" placeholder="0.00" required>
                </div>
                <div class="pv-campo">
                    <label for="r_facturados">Galones facturados</label>
                    <input type="text" name="galones_facturados" id="r_facturados" inputmode="decimal" placeholder="0" required>
                </div>
            </div>

            <div class="pv-campo">
                <label for="r_medidos">Galones medidos en tanque</label>
                <input type="text" name="galones_medidos" id="r_medidos" inputmode="decimal" placeholder="0" required>
            </div>

            <div class="pv-diferencia">
                <span>Diferencia (facturado &minus; medido)</span>
                <span id="r_diferencia">0.0 gal</span>
            </div>
            <p class="pv-ayuda" id="r_diferencia_aviso">Ingresa ambos valores para validar la entrega del proveedor.</p>

            <div class="pv-campo">
                <label for="r_nivel_cm">Nivel medido tras la descarga, en cm (opcional)</label>
                <input type="text" name="nivel_cm" id="r_nivel_cm" inputmode="decimal" placeholder="Dejar en blanco si no vas a actualizar el monitor de tanques">
            </div>
            <p class="pv-ayuda">Si lo llenas, se registra tambien una lectura nueva en el monitor de Tanques (nivel actual + galones medidos).</p>

            <div class="pv-modal__acciones">
                <button type="button" class="pv-btn" id="pv-recepcion-cancelar">Cancelar</button>
                <button type="submit" class="pv-btn pv-btn--primario">Confirmar recepcion</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/proveedores.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
