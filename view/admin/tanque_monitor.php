<?php
require_once __DIR__ . '/../../controller/TanqueController.php';

$tituloPagina    = 'Tanques (Arduino)';
$subtituloPagina = 'Nivel volumetrico via sensor ultrasonico HC-SR04';
$vistaActiva     = 'tanques';
require __DIR__ . '/../layout/header.php';

$tanques   = TanqueController::estadoTanques();
$historial = TanqueController::historialLecturas();

$etiquetaEstado = [
    'verde'    => ['clase' => 'normal',      'texto' => 'Normal'],
    'amarillo' => ['clase' => 'advertencia', 'texto' => 'Advertencia'],
    'rojo'     => ['clase' => 'alerta',      'texto' => 'Stock minimo'],
];
?>
<link rel="stylesheet" href="assets/css/tanques.css">

<div class="tq-page">
    <span class="tq-page__esquina tq-page__esquina--tl"></span>
    <span class="tq-page__esquina tq-page__esquina--tr"></span>
    <span class="tq-page__esquina tq-page__esquina--bl"></span>
    <span class="tq-page__esquina tq-page__esquina--br"></span>

    <div class="tq-cabecera">
        <div>
            <div class="tq-eyebrow">Monitoreo IoT</div>
            <h2 class="tq-titulo">Tanques subterraneos</h2>
        </div>
        <button class="tq-btn tq-btn--primario" type="button" id="tq-btn-nuevo">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nueva lectura
        </button>
    </div>

    <div class="tq-cadena">
        <span>Sensor HC-SR04</span>
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        <span>Arduino (USB)</span>
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        <span>Script puente Python</span>
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        <span>MySQL &middot; lecturas_tanque</span>
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        <span>Este panel</span>
    </div>
    <p class="tq-nota">
        Mientras el Arduino no este conectado, los niveles de abajo se actualizan
        registrando una lectura manual (simula lo que enviaria el sensor).
    </p>

    <div class="tq-grilla">
        <?php foreach ($tanques as $t):
            $porcentaje = $t['capacidad'] > 0 ? round(($t['nivel_actual'] / $t['capacidad']) * 100) : 0;
            $vacio      = max(0, $t['capacidad'] - $t['nivel_actual']);
            $est        = $etiquetaEstado[$t['estado_led']];
            $minimoPct  = $t['capacidad'] > 0 ? round(($t['nivel_minimo'] / $t['capacidad']) * 100) : 0;
        ?>
        <div class="tq-tanque tq-tanque--<?= $est['clase'] ?>">
            <div class="tq-tanque__cabecera">
                <div>
                    <div class="tq-tanque__nombre"><?= htmlspecialchars($t['nombre']) ?></div>
                    <div class="tq-tanque__combustible"><?= htmlspecialchars($t['combustible']) ?></div>
                </div>
                <span class="tq-badge tq-badge--<?= $est['clase'] ?>"><?= $est['texto'] ?></span>
            </div>

            <div class="tq-tanque__cuerpo">
                <div class="tq-barra" title="<?= $porcentaje ?>% lleno">
                    <span class="tq-barra__marca" style="bottom: <?= min(97, $minimoPct) ?>%">
                        <span class="tq-barra__marca-linea"></span>
                    </span>
                    <div class="tq-barra__relleno" style="height: <?= min(100, max(0, $porcentaje)) ?>%"></div>
                </div>

                <div class="tq-tanque__datos">
                    <div class="tq-tanque__valor"><?= number_format($t['nivel_actual']) ?><span>GAL</span></div>
                    <div class="tq-tanque__porcentaje"><?= $porcentaje ?>% lleno</div>

                    <div class="tq-fila"><span>Capacidad</span><span><?= number_format($t['capacidad']) ?> gal</span></div>
                    <div class="tq-fila"><span>Vacio</span><span><?= number_format($vacio) ?> gal</span></div>
                    <div class="tq-fila"><span>Minimo de alerta</span><span><?= number_format($t['nivel_minimo']) ?> gal</span></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="tq-leyenda">
        <span class="tq-badge tq-badge--normal">Normal</span>
        <span class="tq-leyenda__texto">nivel sobre 1.5&times; el minimo de alerta</span>
        <span class="tq-badge tq-badge--advertencia">Advertencia</span>
        <span class="tq-leyenda__texto">entre el minimo y 1.5&times; el minimo</span>
        <span class="tq-badge tq-badge--alerta">Stock minimo</span>
        <span class="tq-leyenda__texto">en o por debajo del minimo de alerta</span>
    </div>

    <div class="tq-historial">
        <div class="tq-historial__titulo">Historial de lecturas del sensor</div>
        <div class="tq-tabla-wrap">
            <table class="tq-tabla">
                <thead><tr><th>Fecha y hora</th><th>Tanque</th><th>Nivel (cm)</th><th>Galones calculados</th></tr></thead>
                <tbody>
                <?php if (empty($historial)): ?>
                    <tr><td colspan="4" class="tq-vacio">Todavia no hay lecturas registradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['fecha']) ?></td>
                        <td><?= htmlspecialchars($h['tanque']) ?></td>
                        <td><?= number_format($h['nivel_cm'], 1) ?> cm</td>
                        <td><?= number_format($h['galones']) ?> gal</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tq-modal-overlay" id="tq-modal-overlay" hidden>
    <div class="tq-modal" role="dialog" aria-modal="true" aria-labelledby="tq-modal-titulo">
        <div class="tq-modal__barra"></div>
        <button class="tq-modal__cerrar" type="button" id="tq-modal-cerrar" aria-label="Cerrar">&times;</button>

        <div class="tq-modal__eyebrow">Tanques &middot; simulacion del sensor</div>
        <div class="tq-modal__titulo" id="tq-modal-titulo">Registrar lectura manual</div>

        <form method="post" action="index.php?accion=tanque_lectura">
            <div class="tq-campo">
                <label for="lt_tanque">Tanque</label>
                <select name="id_tanque" id="lt_tanque">
                    <?php foreach ($tanques as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= htmlspecialchars($t['combustible']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tq-campo-grupo">
                <div class="tq-campo">
                    <label for="lt_nivel_cm">Nivel medido (cm)</label>
                    <input type="text" name="nivel_cm" id="lt_nivel_cm" inputmode="decimal" placeholder="0.00" required>
                </div>
                <div class="tq-campo">
                    <label for="lt_galones">Galones calculados</label>
                    <input type="text" name="galones" id="lt_galones" inputmode="decimal" placeholder="0.00" required>
                </div>
            </div>

            <div class="tq-modal__acciones">
                <button type="button" class="tq-btn" id="tq-modal-cancelar">Cancelar</button>
                <button type="submit" class="tq-btn tq-btn--primario">Guardar lectura</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/tanques.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
