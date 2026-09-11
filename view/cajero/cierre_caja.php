<?php
require_once __DIR__ . '/../../controller/TurnoController.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/Turno.php';

$tituloPagina    = 'Cierre de caja';
$subtituloPagina = 'Conciliacion de caja central / tienda';
$vistaActiva     = 'cierre_caja';
require __DIR__ . '/../layout/header.php';

$turno = TurnoController::obtenerTurnoParaCierre('tienda');

if ($turno === null) {
    echo '<div class="ticket"><div class="ticket__borde-perforado"></div><div class="ticket__cuerpo">'
       . '<p>Todavia no tienes ninguna caja de tienda registrada. Entra a <a href="index.php?vista=pos_tienda">venta en tienda</a> para abrir una.</p>'
       . '</div></div>';
    require __DIR__ . '/../layout/footer.php';
    exit;
}

$conexion      = Database::obtenerConexion();
$ventasPorPago = Turno::obtenerVentasPorTipoPago($conexion, $turno['id_turno']);
$totalTurno    = Turno::totalVentas($conexion, $turno['id_turno']);

$nombresPago = ['efectivo' => 'Ventas en efectivo', 'tarjeta' => 'Ventas con tarjeta', 'mixto' => 'Ventas mixtas'];
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Resumen de ventas del turno #<?= $turno['id_turno'] ?></span></div>

        <?php if (empty($ventasPorPago)): ?>
        <p style="color:var(--c-muted);">Todavia no se ha registrado ninguna venta en este turno.</p>
        <?php endif; ?>
        <?php foreach ($ventasPorPago as $v): ?>
        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta"><?= $nombresPago[$v['metodo_pago']] ?? ucfirst($v['metodo_pago']) ?></span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor">$<?= number_format($v['total'], 2) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="linea-ticket linea-ticket--total">
            <span class="linea-ticket__etiqueta">Total del turno</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor">$<?= number_format($totalTurno, 2) ?></span>
        </div>
    </div>
</div>

<?php if ($turno['estado'] === 'cerrado'): ?>
<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Este turno ya esta cerrado</span></div>
        <div class="linea-ticket linea-ticket--total">
            <span class="linea-ticket__etiqueta">Monto declarado</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor">$<?= number_format($turno['monto_declarado'], 2) ?></span>
        </div>
        <p style="color:var(--c-muted); font-size:0.85rem;">
            Vuelve a <a href="index.php?vista=pos_tienda">venta en tienda</a> para abrir la siguiente caja.
        </p>
    </div>
</div>
<?php else: ?>
<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Entrega de efectivo</span></div>

        <form method="post" action="index.php?accion=cerrar_caja">
            <div class="campo">
                <label for="monto_entregado">Monto declarado por el cajero</label>
                <input type="text" name="monto_declarado" id="monto_entregado" inputmode="decimal" placeholder="0.00" required>
            </div>

            <div class="linea-ticket linea-ticket--total">
                <span class="linea-ticket__etiqueta">Diferencia vs. total del turno ($<?= number_format($totalTurno, 2) ?>)</span>
                <span class="linea-ticket__relleno"></span>
                <span class="linea-ticket__valor" id="diferencia-caja">$0.00</span>
            </div>

            <button class="btn btn--lleno" type="submit">Confirmar cierre de caja</button>
        </form>
    </div>
</div>

<script>
(function () {
    const totalTurno = <?= json_encode($totalTurno) ?>;
    const input = document.getElementById('monto_entregado');
    const salida = document.getElementById('diferencia-caja');
    input.addEventListener('input', function () {
        const declarado = parseFloat(input.value) || 0;
        salida.textContent = '$' + (declarado - totalTurno).toFixed(2);
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
