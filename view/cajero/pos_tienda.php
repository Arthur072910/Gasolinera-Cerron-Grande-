<?php
require_once __DIR__ . '/../../controller/TiendaController.php';
require_once __DIR__ . '/../../controller/TurnoController.php';

$tituloPagina    = 'Venta en tienda';
$subtituloPagina = 'Caja central &middot; tienda de conveniencia';
$vistaActiva     = 'pos_tienda';
require __DIR__ . '/../layout/header.php';

$categorias  = TiendaController::categorias();
$productos   = TiendaController::productos();
$metodosPago = TiendaController::metodosPago();
$comprobantes= TiendaController::tiposComprobante();
$turno       = TurnoController::obtenerOAbrirTurnoActivo('tienda');
?>

<div class="visor" style="margin-bottom:24px;">
    <div class="visor__etiqueta">Turno activo &middot; #<?= $turno['id_turno'] ?></div>
    <div class="visor__digitos"><?= htmlspecialchars($turno['hora_inicio']) ?> &mdash; <?= htmlspecialchars($turno['hora_prevista']) ?></div>
    <div style="margin-top:8px; font-size:0.8rem; color:#9a9a9a;">
        Fondo inicial: $<?= number_format($turno['monto_inicial'], 2) ?> &middot; Estado: <?= htmlspecialchars($turno['estado']) ?>
    </div>
</div>

<form id="form-venta-tienda" method="post" action="index.php?accion=venta_tienda">
<input type="hidden" name="carrito" id="input-carrito">
<input type="hidden" name="metodo_pago" id="input-metodo-pago">
<input type="hidden" name="tipo_comprobante" id="input-tipo-comprobante" value="ticket">

<div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:24px; align-items:start;">

    <div class="ticket">
        <div class="ticket__borde-perforado"></div>
        <div class="ticket__cuerpo">
            <div class="ticket__titulo"><span>Productos</span></div>

            <div class="buscador">
                <input type="text" id="buscador-productos" placeholder="Buscar producto por nombre o codigo de barras...">
            </div>

            <div class="pestanas" id="pestanas-categorias">
                <button class="activa" data-categoria="todas">Todas</button>
                <?php foreach ($categorias as $c): ?>
                <button data-categoria="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="grilla-teclas" id="grilla-productos">
                <?php foreach ($productos as $p): ?>
                <button class="tecla" type="button"
                        data-producto-id="<?= $p['id'] ?>"
                        data-categoria="<?= $p['id_categoria'] ?>"
                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                        data-precio="<?= $p['precio'] ?>"
                        data-codigo="<?= htmlspecialchars($p['codigo_barras']) ?>">
                    <div class="tecla__titulo">$<?= number_format($p['precio'], 2) ?></div>
                    <div class="tecla__meta"><?= htmlspecialchars($p['nombre']) ?> &middot; stock <?= $p['stock'] ?></div>
                </button>
                <?php endforeach; ?>
            </div>
            <p id="sin-resultados" style="display:none; color:var(--c-muted); font-size:0.85rem; margin-top:12px;">
                No hay productos que coincidan con la busqueda.
            </p>
        </div>
    </div>

    <div class="ticket">
        <div class="ticket__borde-perforado"></div>
        <div class="ticket__cuerpo">
            <div class="ticket__titulo"><span>Ticket actual</span></div>

            <div id="lista-carrito">
                <div class="carrito-vacio" id="carrito-vacio-msg">Toca un producto para agregarlo</div>
            </div>

            <div class="linea-ticket linea-ticket--total">
                <span class="linea-ticket__etiqueta">Total</span>
                <span class="linea-ticket__relleno"></span>
                <span class="linea-ticket__valor" id="carrito-total">$0.00</span>
            </div>

            <div class="bloque-paso__titulo" style="margin-top:16px;">Metodo de pago</div>
            <div class="chips" id="chips-pago">
                <?php foreach ($metodosPago as $m): ?>
                <button class="chip" type="button" data-pago="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="bloque-paso__titulo">Comprobante</div>
            <div class="chips" id="chips-comprobante">
                <?php foreach ($comprobantes as $c): ?>
                <button class="chip" type="button" data-comprobante="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></button>
                <?php endforeach; ?>
            </div>

            <div id="campos-fiscales" style="display:none;">
                <div class="campo">
                    <label for="nit_cliente">NIT / NRC del cliente</label>
                    <input type="text" id="nit_cliente" placeholder="0000-000000-000-0">
                </div>
                <div class="campo">
                    <label for="nombre_cliente">Nombre o razon social</label>
                    <input type="text" id="nombre_cliente" placeholder="Nombre del cliente">
                </div>
            </div>

            <button class="btn btn--lleno btn--ancho" id="btn-cobrar" type="button">Cobrar</button>
        </div>
    </div>
</div>
</form>

<script src="assets/js/pos_tienda.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
