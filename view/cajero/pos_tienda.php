<?php
require_once __DIR__ . '/../../controller/TiendaController.php';
require_once __DIR__ . '/../../controller/TurnoController.php';

$tituloPagina    = 'Venta en tienda';
$subtituloPagina = 'Caja central &middot; tienda de conveniencia';
$vistaActiva     = 'pos_tienda';
require __DIR__ . '/../layout/header.php';

$categorias   = TiendaController::categorias();
$productos    = TiendaController::productos();
$metodosPago  = TiendaController::metodosPago();
$comprobantes = TiendaController::tiposComprobante();
$turno        = TurnoController::obtenerOAbrirTurnoActivo('tienda');
$ultimoTicket = Sesion::leerUltimoTicket();
?>
<link rel="stylesheet" href="assets/css/pos_tienda.css">

<div class="pt-page">
    <span class="pt-page__esquina pt-page__esquina--tl"></span>
    <span class="pt-page__esquina pt-page__esquina--tr"></span>
    <span class="pt-page__esquina pt-page__esquina--bl"></span>
    <span class="pt-page__esquina pt-page__esquina--br"></span>

    <div class="pt-turno">
        <div class="pt-turno__bloque">
            <div class="pt-turno__etiqueta">Turno activo</div>
            <div class="pt-turno__valor">#<?= (int) $turno['id_turno'] ?></div>
        </div>
        <div class="pt-turno__bloque">
            <div class="pt-turno__etiqueta">Horario</div>
            <div class="pt-turno__valor pt-turno__valor--mono"><?= htmlspecialchars($turno['hora_inicio']) ?> &mdash; <?= htmlspecialchars($turno['hora_prevista']) ?></div>
        </div>
        <div class="pt-turno__bloque">
            <div class="pt-turno__etiqueta">Fondo inicial</div>
            <div class="pt-turno__valor pt-turno__valor--mono">$<?= number_format($turno['monto_inicial'], 2) ?></div>
        </div>
        <div class="pt-turno__bloque pt-turno__bloque--estado">
            <span class="pt-badge pt-badge--normal"><span></span> <?= htmlspecialchars(ucfirst((string) $turno['estado'])) ?></span>
            <a class="pt-turno__enlace" href="index.php?vista=cierre_caja">Cerrar caja &rarr;</a>
        </div>
    </div>

    <form id="form-venta-tienda" method="post" action="index.php?accion=venta_tienda">
        <input type="hidden" name="carrito" id="input-carrito">
        <input type="hidden" name="metodo_pago" id="input-metodo-pago">
        <input type="hidden" name="tipo_comprobante" id="input-tipo-comprobante" value="ticket">
        <input type="hidden" name="nombre_cliente" id="input-nombre-cliente">
        <input type="hidden" name="nit_cliente" id="input-nit-cliente">
        <input type="hidden" name="monto_recibido" id="input-monto-recibido">

        <div class="pt-layout">
            <div class="pt-panel">
                <div class="pt-buscador">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="buscador-productos" placeholder="Buscar producto por nombre o codigo de barras...">
                </div>

                <div class="pt-tabs" id="pestanas-categorias">
                    <button class="activa" type="button" data-categoria="todas">Todas</button>
                    <?php foreach ($categorias as $c): ?>
                    <button type="button" data-categoria="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="pt-grilla" id="grilla-productos">
                    <?php foreach ($productos as $p):
                        $sinStock = (int) $p['stock'] <= 0;
                    ?>
                    <button class="pt-producto<?= $sinStock ? ' pt-producto--agotado' : '' ?>" type="button"
                            data-producto-id="<?= (int) $p['id'] ?>"
                            data-categoria="<?= (int) $p['id_categoria'] ?>"
                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                            data-precio="<?= $p['precio'] ?>"
                            data-stock="<?= (int) $p['stock'] ?>"
                            data-codigo="<?= htmlspecialchars((string) $p['codigo_barras']) ?>"
                            <?= $sinStock ? 'disabled' : '' ?>>
                        <div class="pt-producto__precio">$<?= number_format($p['precio'], 2) ?></div>
                        <div class="pt-producto__nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="pt-producto__stock"><?= $sinStock ? 'Agotado' : 'Stock ' . (int) $p['stock'] ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="pt-vacio" id="sin-resultados" hidden>No hay productos que coincidan con la busqueda.</p>
            </div>

            <div class="pt-panel pt-panel--ticket">
                <div class="pt-ticket__titulo">Ticket actual</div>

                <div class="pt-carrito" id="lista-carrito">
                    <div class="pt-carrito-vacio" id="carrito-vacio-msg">Toca un producto para agregarlo</div>
                </div>

                <div class="pt-total">
                    <span>Total</span>
                    <span id="carrito-total">$0.00</span>
                </div>

                <div class="pt-seccion">Metodo de pago</div>
                <div class="pt-chips" id="chips-pago">
                    <?php foreach ($metodosPago as $m): ?>
                    <button class="pt-chip" type="button" data-pago="<?= htmlspecialchars($m['id']) ?>"><?= htmlspecialchars($m['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="pt-campo-efectivo" id="bloque-efectivo" hidden>
                    <label for="monto-recibido">Efectivo recibido</label>
                    <input type="text" id="monto-recibido" inputmode="decimal" placeholder="0.00">
                    <div class="pt-campo-efectivo__cambio">
                        <span>Cambio a entregar</span>
                        <span id="monto-cambio">$0.00</span>
                    </div>
                </div>

                <div class="pt-seccion">Comprobante</div>
                <div class="pt-chips" id="chips-comprobante">
                    <?php foreach ($comprobantes as $c): ?>
                    <button class="pt-chip<?= $c['id'] === 'ticket' ? ' activo' : '' ?>" type="button" data-comprobante="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="pt-campos-fiscales" id="campos-fiscales" hidden>
                    <div class="pt-campo">
                        <label for="campo-nombre-cliente">Nombre o razon social</label>
                        <input type="text" id="campo-nombre-cliente" placeholder="Nombre del cliente">
                    </div>
                    <div class="pt-campo">
                        <label for="campo-nit-cliente">NIT / NRC del cliente</label>
                        <input type="text" id="campo-nit-cliente" placeholder="0000-000000-000-0">
                    </div>
                </div>

                <button class="pt-btn-cobrar" id="btn-cobrar" type="button">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    Cobrar
                </button>
            </div>
        </div>
    </form>

    <div class="pt-recibo-overlay" id="pt-recibo-overlay" hidden>
        <div class="pt-recibo">
            <button class="pt-recibo__cerrar" id="pt-recibo-cerrar" type="button" aria-label="Cerrar">&times;</button>
            <div class="pt-recibo__contenido" id="pt-recibo-contenido"></div>
            <div class="pt-recibo__acciones">
                <button class="pt-btn-secundario" id="pt-recibo-cerrar-btn" type="button">Cerrar</button>
                <button class="pt-btn-cobrar pt-btn-imprimir" id="pt-recibo-imprimir" type="button">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.DATOS_ULTIMO_TICKET = <?= $ultimoTicket ? json_encode($ultimoTicket) : 'null' ?>;
    window.NOMBRE_NEGOCIO = <?= json_encode(NOMBRE_SISTEMA) ?>;
</script>
<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/pos_tienda.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
