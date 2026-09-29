<?php
require_once __DIR__ . '/../../controller/TurnoController.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/LecturaTurno.php';

$tituloPagina    = 'Cierre de turno';
$subtituloPagina = 'Conciliacion de caja de pista';
$vistaActiva     = 'cierre_turno';
require __DIR__ . '/../layout/header.php';

$resumen   = TurnoController::obtenerResumenCierre('pista');
$historial = TurnoController::obtenerHistorialCierres('pista');
if ($resumen !== null && $resumen['estado'] === 'cerrado') {
    $historial = array_values(array_filter($historial, fn ($h) => $h['id_turno'] !== $resumen['id_turno']));
}

$lecturas = $resumen !== null ? LecturaTurno::obtenerResumenTurno(Database::obtenerConexion(), $resumen['id_turno']) : [];
$totalGalonesDespachados = 0.0;
foreach ($lecturas as $l) {
    $totalGalonesDespachados += (float) $l['contador_actual'] - (float) $l['contador_inicial'];
}

$nombresPago = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'mixto' => 'Mixto'];
?>
<link rel="stylesheet" href="assets/css/cierre_turno.css">

<div class="ct-page">
    <span class="ct-page__esquina ct-page__esquina--tl"></span>
    <span class="ct-page__esquina ct-page__esquina--tr"></span>
    <span class="ct-page__esquina ct-page__esquina--bl"></span>
    <span class="ct-page__esquina ct-page__esquina--br"></span>

    <?php if ($resumen === null): ?>
        <div class="ct-vacio">
            <div class="ct-vacio__icono">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22V4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v18"/><path d="M3 10h8"/><path d="M15 22V9l4-2v13a2 2 0 0 0 2 2"/></svg>
            </div>
            <p>Todavia no tienes ningun turno de pista registrado.</p>
            <a class="ct-btn ct-btn--primario" href="index.php?vista=pos_pista">Ir a despacho en pista</a>
        </div>
    <?php else: ?>

    <div class="ct-cabecera">
        <div>
            <div class="ct-eyebrow">Conciliacion</div>
            <h2 class="ct-titulo">Turno #<?= (int) $resumen['id_turno'] ?></h2>
        </div>
        <span class="ct-badge ct-badge--<?= $resumen['estado'] === 'abierto' ? 'normal' : 'cerrado' ?>">
            <span></span> <?= $resumen['estado'] === 'abierto' ? 'Turno abierto' : 'Turno cerrado' ?>
        </span>
    </div>

    <div class="ct-bloque">
        <div class="ct-bloque__titulo">Lecturas de manguera</div>
        <div class="ct-tabla-wrap">
            <table class="ct-tabla">
                <thead><tr><th>Manguera</th><th>Combustible</th><th>Contador inicial</th><th>Contador actual</th><th>Despachado</th></tr></thead>
                <tbody>
                <?php if (empty($lecturas)): ?>
                    <tr><td colspan="5" class="ct-vacio-fila">Sin lecturas registradas para este turno.</td></tr>
                <?php else: foreach ($lecturas as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['color_identificador']) ?></td>
                        <td><?= ucfirst($l['tipo_combustible']) ?></td>
                        <td><?= number_format($l['contador_inicial'], 2) ?> gal</td>
                        <td><?= number_format($l['contador_actual'], 2) ?> gal</td>
                        <td><?= number_format($l['contador_actual'] - $l['contador_inicial'], 2) ?> gal</td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ct-total-galones">
            <span>Total despachado</span>
            <span><?= number_format($totalGalonesDespachados, 2) ?> gal</span>
        </div>
    </div>

    <div class="ct-resumen">
        <div class="ct-resumen__item">
            <div class="ct-resumen__etiqueta">Fondo inicial</div>
            <div class="ct-resumen__valor">$<?= number_format($resumen['monto_inicial'], 2) ?></div>
        </div>
        <?php foreach ($nombresPago as $clave => $etiqueta):
            $encontrado = null;
            foreach ($resumen['ventas_por_pago'] as $v) {
                if ($v['metodo_pago'] === $clave) { $encontrado = $v; break; }
            }
        ?>
        <div class="ct-resumen__item">
            <div class="ct-resumen__etiqueta">Ventas <?= htmlspecialchars($etiqueta) ?></div>
            <div class="ct-resumen__valor">$<?= number_format($encontrado ? $encontrado['total'] : 0, 2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="ct-resumen__item ct-resumen__item--total">
            <div class="ct-resumen__etiqueta">Total del turno</div>
            <div class="ct-resumen__valor">$<?= number_format($resumen['total_turno'], 2) ?></div>
        </div>
    </div>

    <div class="ct-nota">
        Solo el efectivo queda fisicamente en la gaveta. Las ventas con tarjeta no se cuentan al declarar el efectivo.
    </div>

    <div class="ct-efectivo-esperado">
        <div>
            <div class="ct-efectivo-esperado__etiqueta">Efectivo esperado en caja</div>
            <div class="ct-efectivo-esperado__formula">Fondo inicial ($<?= number_format($resumen['monto_inicial'], 2) ?>) + ventas en efectivo ($<?= number_format($resumen['ventas_efectivo'], 2) ?>)</div>
        </div>
        <div class="ct-efectivo-esperado__valor">$<?= number_format($resumen['efectivo_esperado'], 2) ?></div>
    </div>

    <?php if ($resumen['estado'] === 'cerrado'): ?>
        <div class="ct-cierre-final">
            <div class="ct-cierre-final__fila">
                <span>Efectivo declarado por el despachador</span>
                <span>$<?= number_format($resumen['monto_declarado'], 2) ?></span>
            </div>
            <div class="ct-cierre-final__fila ct-cierre-final__fila--diferencia">
                <span>Diferencia</span>
                <span class="ct-diferencia ct-diferencia--<?= $resumen['diferencia'] == 0 ? 'exacto' : ($resumen['diferencia'] > 0 ? 'sobra' : 'falta') ?>">
                    <?= $resumen['diferencia'] > 0 ? '+' : '' ?>$<?= number_format($resumen['diferencia'], 2) ?>
                    <?= $resumen['diferencia'] == 0 ? '(exacto)' : ($resumen['diferencia'] > 0 ? '(sobrante)' : '(faltante)') ?>
                </span>
            </div>
            <a class="ct-btn ct-btn--primario ct-btn--ancho" href="index.php?vista=pos_pista">Abrir el siguiente turno</a>
        </div>
    <?php else: ?>
        <form class="ct-form" method="post" action="index.php?accion=cerrar_turno" id="form-cierre-turno">
            <div class="ct-campo">
                <label for="monto_entregado">Efectivo entregado (contado)</label>
                <input type="text" name="monto_declarado" id="monto_entregado" inputmode="decimal" placeholder="0.00" autocomplete="off" required>
            </div>

            <div class="ct-diferencia-vivo">
                <span>Diferencia vs. efectivo esperado ($<?= number_format($resumen['efectivo_esperado'], 2) ?>)</span>
                <span id="diferencia-turno" class="ct-diferencia ct-diferencia--pendiente">Pendiente de contar</span>
            </div>

            <button class="ct-btn ct-btn--primario ct-btn--ancho" type="submit" id="btn-confirmar-cierre">Confirmar cierre de turno</button>
            <p class="ct-aclaracion">Esto solo cierra el cuadre de este turno (el dinero y las lecturas de manguera). Tu asistencia del dia sigue activa: cuando termines tu jornada, cierra sesion desde el menu lateral.</p>
        </form>
    <?php endif; ?>

    <?php if (!empty($historial)): ?>
    <div class="ct-historial">
        <div class="ct-historial__titulo">Cierres anteriores</div>
        <div class="ct-historial__tabla-wrap">
            <table class="ct-historial__tabla">
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
                            <span class="ct-diferencia ct-diferencia--<?= $h['diferencia'] == 0 ? 'exacto' : ($h['diferencia'] > 0 ? 'sobra' : 'falta') ?>">
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
    window.DATOS_CIERRE_TURNO = <?= json_encode(['efectivoEsperado' => $resumen['efectivo_esperado'] ?? 0]) ?>;
</script>
<script src="assets/js/cierre_turno.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
