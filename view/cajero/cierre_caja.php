<?php
require_once __DIR__ . '/../../controller/TurnoController.php';

$tituloPagina    = 'Cierre de caja';
$subtituloPagina = 'Conciliacion de caja central / tienda';
$vistaActiva     = 'cierre_caja';
require __DIR__ . '/../layout/header.php';

$resumen   = TurnoController::obtenerResumenCierre('tienda');
$historial = TurnoController::obtenerHistorialCierres('tienda');
// El turno que ya se muestra en detalle arriba (si esta cerrado) no se
// repite en la lista de "cierres anteriores".
if ($resumen !== null && $resumen['estado'] === 'cerrado') {
    $historial = array_values(array_filter($historial, fn ($h) => $h['id_turno'] !== $resumen['id_turno']));
}

$nombresPago = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'mixto' => 'Mixto'];
?>
<link rel="stylesheet" href="assets/css/cierre_caja.css">

<div class="cc-page">
    <span class="cc-page__esquina cc-page__esquina--tl"></span>
    <span class="cc-page__esquina cc-page__esquina--tr"></span>
    <span class="cc-page__esquina cc-page__esquina--bl"></span>
    <span class="cc-page__esquina cc-page__esquina--br"></span>

    <?php if ($resumen === null): ?>
        <div class="cc-vacio">
            <div class="cc-vacio__icono">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <p>Todavia no tienes ninguna caja de tienda registrada.</p>
            <a class="cc-btn cc-btn--primario" href="index.php?vista=pos_tienda">Ir a venta en tienda</a>
        </div>
    <?php else: ?>

    <div class="cc-cabecera">
        <div>
            <div class="cc-eyebrow">Conciliacion</div>
            <h2 class="cc-titulo">Turno #<?= (int) $resumen['id_turno'] ?></h2>
        </div>
        <span class="cc-badge cc-badge--<?= $resumen['estado'] === 'abierto' ? 'normal' : 'cerrado' ?>">
            <span></span> <?= $resumen['estado'] === 'abierto' ? 'Turno abierto' : 'Turno cerrado' ?>
        </span>
    </div>

    <div class="cc-resumen">
        <div class="cc-resumen__item">
            <div class="cc-resumen__etiqueta">Fondo inicial</div>
            <div class="cc-resumen__valor">$<?= number_format($resumen['monto_inicial'], 2) ?></div>
        </div>
        <?php foreach ($nombresPago as $clave => $etiqueta):
            $encontrado = null;
            foreach ($resumen['ventas_por_pago'] as $v) {
                if ($v['metodo_pago'] === $clave) { $encontrado = $v; break; }
            }
        ?>
        <div class="cc-resumen__item">
            <div class="cc-resumen__etiqueta">Ventas <?= htmlspecialchars($etiqueta) ?></div>
            <div class="cc-resumen__valor">$<?= number_format($encontrado ? $encontrado['total'] : 0, 2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="cc-resumen__item cc-resumen__item--total">
            <div class="cc-resumen__etiqueta">Total del turno</div>
            <div class="cc-resumen__valor">$<?= number_format($resumen['total_turno'], 2) ?></div>
        </div>
    </div>

    <div class="cc-nota">
        Solo el efectivo queda fisicamente en la gaveta. Las ventas con tarjeta no se cuentan al declarar el efectivo.
    </div>

    <div class="cc-efectivo-esperado">
        <div>
            <div class="cc-efectivo-esperado__etiqueta">Efectivo esperado en caja</div>
            <div class="cc-efectivo-esperado__formula">Fondo inicial ($<?= number_format($resumen['monto_inicial'], 2) ?>) + ventas en efectivo ($<?= number_format($resumen['ventas_efectivo'], 2) ?>)</div>
        </div>
        <div class="cc-efectivo-esperado__valor">$<?= number_format($resumen['efectivo_esperado'], 2) ?></div>
    </div>

    <?php if ($resumen['estado'] === 'cerrado'): ?>
        <div class="cc-cierre-final">
            <div class="cc-cierre-final__fila">
                <span>Monto declarado por el cajero</span>
                <span>$<?= number_format($resumen['monto_declarado'], 2) ?></span>
            </div>
            <div class="cc-cierre-final__fila cc-cierre-final__fila--diferencia">
                <span>Diferencia</span>
                <span class="cc-diferencia cc-diferencia--<?= $resumen['diferencia'] == 0 ? 'exacto' : ($resumen['diferencia'] > 0 ? 'sobra' : 'falta') ?>">
                    <?= $resumen['diferencia'] > 0 ? '+' : '' ?>$<?= number_format($resumen['diferencia'], 2) ?>
                    <?= $resumen['diferencia'] == 0 ? '(exacto)' : ($resumen['diferencia'] > 0 ? '(sobrante)' : '(faltante)') ?>
                </span>
            </div>
            <a class="cc-btn cc-btn--primario cc-btn--ancho" href="index.php?vista=pos_tienda">Abrir la siguiente caja</a>
        </div>
    <?php else: ?>
        <form class="cc-form" method="post" action="index.php?accion=cerrar_caja" id="form-cierre-caja">
            <div class="cc-campo">
                <label for="monto_entregado">Monto declarado por el cajero (efectivo contado)</label>
                <input type="text" name="monto_declarado" id="monto_entregado" inputmode="decimal" placeholder="0.00" autocomplete="off" required>
            </div>

            <div class="cc-diferencia-vivo">
                <span>Diferencia vs. efectivo esperado ($<?= number_format($resumen['efectivo_esperado'], 2) ?>)</span>
                <span id="diferencia-caja" class="cc-diferencia cc-diferencia--exacto">$0.00</span>
            </div>

            <button class="cc-btn cc-btn--primario cc-btn--ancho" type="submit" id="btn-confirmar-cierre">Confirmar cierre de caja</button>
            <p class="cc-aclaracion">Esto solo cierra el cuadre de esta caja (el dinero). Tu asistencia del dia sigue activa: cuando termines tu jornada, cierra sesion desde el menu lateral.</p>
        </form>
    <?php endif; ?>

    <?php if (!empty($historial)): ?>
    <div class="cc-historial">
        <div class="cc-historial__titulo">Cierres anteriores</div>
        <div class="cc-historial__tabla-wrap">
            <table class="cc-historial__tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Fondo</th>
                        <th>Esperado</th>
                        <th>Declarado</th>
                        <th>Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($h['fecha_fin']))) ?></td>
                        <td>$<?= number_format($h['monto_inicial'], 2) ?></td>
                        <td>$<?= number_format($h['efectivo_esperado'], 2) ?></td>
                        <td>$<?= number_format($h['monto_declarado'], 2) ?></td>
                        <td>
                            <span class="cc-diferencia cc-diferencia--<?= $h['diferencia'] == 0 ? 'exacto' : ($h['diferencia'] > 0 ? 'sobra' : 'falta') ?>">
                                <?= $h['diferencia'] > 0 ? '+' : '' ?>$<?= number_format($h['diferencia'], 2) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script>
    window.DATOS_CIERRE_CAJA = <?= json_encode(['efectivoEsperado' => $resumen['efectivo_esperado'] ?? 0]) ?>;
</script>
<script src="assets/js/cierre_caja.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
