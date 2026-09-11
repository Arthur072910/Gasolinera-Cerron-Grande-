<?php
require_once __DIR__ . '/../../controller/TurnoController.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/Turno.php';
require_once __DIR__ . '/../../model/LecturaTurno.php';

$tituloPagina    = 'Cierre de turno';
$subtituloPagina = 'Conciliacion de caja de pista';
$vistaActiva     = 'cierre_turno';
require __DIR__ . '/../layout/header.php';

$turno = TurnoController::obtenerTurnoParaCierre('pista');

if ($turno === null) {
    echo '<div class="ticket"><div class="ticket__borde-perforado"></div><div class="ticket__cuerpo">'
       . '<p>Todavia no tienes ningun turno de pista registrado. Entra a <a href="index.php?vista=pos_pista">despacho en pista</a> para abrir uno.</p>'
       . '</div></div>';
    require __DIR__ . '/../layout/footer.php';
    exit;
}

$conexion     = Database::obtenerConexion();
$lecturas     = LecturaTurno::obtenerResumenTurno($conexion, $turno['id_turno']);
$totalGalones = 0.0;
foreach ($lecturas as $l) {
    $totalGalones += (float) $l['contador_actual'] - (float) $l['contador_inicial'];
}
$ventaTeorica = Turno::totalVentas($conexion, $turno['id_turno']);
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Lecturas de manguera &middot; turno #<?= $turno['id_turno'] ?></span></div>

        <table class="bitacora">
            <thead><tr><th>Manguera</th><th>Combustible</th><th>Contador inicial</th><th>Contador actual</th><th>Despachado</th></tr></thead>
            <tbody>
            <?php foreach ($lecturas as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l['color_identificador']) ?></td>
                    <td><?= ucfirst($l['tipo_combustible']) ?></td>
                    <td><?= number_format($l['contador_inicial'], 2) ?> gal</td>
                    <td><?= number_format($l['contador_actual'], 2) ?> gal</td>
                    <td><?= number_format($l['contador_actual'] - $l['contador_inicial'], 2) ?> gal</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="linea-ticket linea-ticket--total" style="margin-top:12px;">
            <span class="linea-ticket__etiqueta">Total despachado</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor"><?= number_format($totalGalones, 2) ?> gal</span>
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
            Vuelve a <a href="index.php?vista=pos_pista">despacho en pista</a> para abrir el siguiente turno.
        </p>
    </div>
</div>
<?php else: ?>
<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Conciliacion de caja</span></div>

        <div class="linea-ticket">
            <span class="linea-ticket__etiqueta">Venta teorica (segun bomba)</span>
            <span class="linea-ticket__relleno"></span>
            <span class="linea-ticket__valor">$<?= number_format($ventaTeorica, 2) ?></span>
        </div>

        <form method="post" action="index.php?accion=cerrar_turno">
            <div class="campo">
                <label for="monto_entregado">Efectivo entregado</label>
                <input type="text" name="monto_declarado" id="monto_entregado" inputmode="decimal" placeholder="0.00" required>
            </div>
            <div class="linea-ticket linea-ticket--total">
                <span class="linea-ticket__etiqueta">Diferencia</span>
                <span class="linea-ticket__relleno"></span>
                <span class="linea-ticket__valor" id="diferencia-turno">$0.00</span>
            </div>
            <button class="btn btn--lleno" type="submit">Confirmar cierre de turno</button>
        </form>
    </div>
</div>

<script>
(function () {
    const ventaTeorica = <?= json_encode($ventaTeorica) ?>;
    const input = document.getElementById('monto_entregado');
    const salida = document.getElementById('diferencia-turno');
    input.addEventListener('input', function () {
        const declarado = parseFloat(input.value) || 0;
        salida.textContent = '$' + (declarado - ventaTeorica).toFixed(2);
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
