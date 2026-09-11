<?php
require_once __DIR__ . '/../../controller/TanqueController.php';

$tituloPagina    = 'Tanques (Arduino)';
$subtituloPagina = 'Nivel volumetrico via sensor ultrasonico HC-SR04';
$vistaActiva     = 'tanques';
require __DIR__ . '/../layout/header.php';

$tanques   = TanqueController::estadoTanques();
$historial = TanqueController::historialLecturas();

$etiquetaEstado = [
    'verde'    => ['clase' => 'normal',      'texto' => 'Nivel normal'],
    'amarillo' => ['clase' => 'advertencia', 'texto' => 'Nivel medio'],
    'rojo'     => ['clase' => 'alerta',      'texto' => 'Stock minimo'],
];
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Como llegan estos datos</span>
        </div>
        <p style="margin:0 0 8px;">
            Sensor HC-SR04 &rarr; Arduino (puerto serial USB) &rarr; script puente en Python
            &rarr; base de datos MySQL (tabla <code>lecturas_tanque</code>) &rarr; esta pantalla
            (via fetch/AJAX cada 2-3 segundos).
        </p>
        <p style="margin:0; color:var(--c-muted); font-size:0.85rem;">
            Mientras el Arduino no este conectado, los niveles de abajo se
            actualizan registrando una lectura manual desde el formulario
            de mas abajo (simula lo que enviaria el sensor).
        </p>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Registrar lectura manual (simulacion del sensor)</span>
            <button class="btn" type="button" id="btn-mostrar-form-lectura">+ Nueva lectura</button>
        </div>
        <div class="bloque-paso" id="form-lectura" data-oculto="1">
            <form method="post" action="index.php?accion=tanque_lectura">
                <div class="form-grid">
                    <div class="campo">
                        <label for="lt_tanque">Tanque</label>
                        <select name="id_tanque" id="lt_tanque">
                            <?php foreach ($tanques as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= htmlspecialchars($t['combustible']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="lt_nivel_cm">Nivel medido (cm)</label>
                        <input type="text" name="nivel_cm" id="lt_nivel_cm" inputmode="decimal" placeholder="0.00" required>
                    </div>
                    <div class="campo">
                        <label for="lt_galones">Galones calculados</label>
                        <input type="text" name="galones" id="lt_galones" inputmode="decimal" placeholder="0.00" required>
                    </div>
                </div>
                <button type="submit" class="btn btn--lleno">Guardar lectura</button>
            </form>
        </div>
    </div>
</div>

<div class="grilla-teclas">
    <?php foreach ($tanques as $t):
        $porcentaje = round(($t['nivel_actual'] / $t['capacidad']) * 100);
        $est = $etiquetaEstado[$t['estado_led']];
    ?>
    <div class="visor" style="grid-column: span 1;">
        <div class="visor__etiqueta"><?= htmlspecialchars($t['nombre']) ?> &middot; <?= htmlspecialchars($t['combustible']) ?></div>
        <div class="visor__digitos"><?= number_format($t['nivel_actual']) ?><span class="visor__unidad">/ <?= number_format($t['capacidad']) ?> gal</span></div>

        <div class="nivel-barra">
            <div class="nivel-barra__relleno" style="width: <?= $porcentaje ?>%;"></div>
        </div>

        <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
            <span class="estado estado--<?= $est['clase'] ?>">
                <span class="estado__marca"></span> <?= $est['texto'] ?>
            </span>
            <span style="font-size:0.75rem; color:#9a9a9a;"><?= $porcentaje ?>%</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="ticket" style="margin-top:24px;">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Leyenda de estado (por forma, no por color)</span></div>
        <div style="display:flex; gap:24px; flex-wrap:wrap;">
            <span class="estado estado--normal"><span class="estado__marca"></span> Normal &mdash; circulo hueco</span>
            <span class="estado estado--advertencia"><span class="estado__marca"></span> Advertencia &mdash; medio relleno</span>
            <span class="estado estado--alerta"><span class="estado__marca"></span> Alerta &mdash; solido</span>
        </div>
    </div>
</div>

<div class="ticket" style="margin-top:24px;">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Historial de lecturas del sensor</span></div>
        <table class="bitacora">
            <thead><tr><th>Fecha y hora</th><th>Tanque</th><th>Nivel (cm)</th><th>Galones calculados</th></tr></thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['fecha']) ?></td>
                    <td><?= htmlspecialchars($h['tanque']) ?></td>
                    <td><?= number_format($h['nivel_cm'], 1) ?></td>
                    <td><?= number_format($h['galones']) ?> gal</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="color:var(--c-muted); font-size:0.78rem; margin-top:12px;">
            Esta tabla representa <code>lecturas_tanque</code>: el script puente en
            Python inserta una fila cada vez que el sensor HC-SR04 reporta un nuevo valor.
        </p>
    </div>
</div>

<script src="assets/js/formularios.js"></script>
<script src="assets/js/tanque_monitor.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
