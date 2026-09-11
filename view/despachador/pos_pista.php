<?php
require_once __DIR__ . '/../../controller/DespachoController.php';
require_once __DIR__ . '/../../controller/TurnoController.php';

$tituloPagina    = 'Despacho en pista';
$subtituloPagina = 'Turno activo &middot; caja de pista';
$vistaActiva     = 'pos_pista';
require __DIR__ . '/../layout/header.php';

$bombas      = DespachoController::bombas();
$modalidades = DespachoController::modalidadesVenta();
$precios     = DespachoController::precioVigente();
$turno       = TurnoController::obtenerOAbrirTurnoActivo('pista');
?>

<div class="visor" style="margin-bottom:24px;">
    <div class="visor__etiqueta">Turno activo &middot; #<?= $turno['id_turno'] ?></div>
    <div class="visor__digitos"><?= htmlspecialchars($turno['hora_inicio']) ?> &mdash; <?= htmlspecialchars($turno['hora_prevista']) ?></div>
    <div style="margin-top:8px; font-size:0.8rem; color:#9a9a9a;">
        Fondo inicial: $<?= number_format($turno['monto_inicial'], 2) ?> &middot; Estado: <?= htmlspecialchars($turno['estado']) ?>
        &middot; Seleccion actual: <span id="resumen-seleccion">ninguna</span>
    </div>
</div>

<form id="form-despacho" method="post" action="index.php?accion=despacho">
<input type="hidden" name="id_manguera" id="input-id-manguera">
<input type="hidden" name="modalidad" id="input-modalidad">
<input type="hidden" name="valor_entrada" id="input-valor-entrada">
<input type="hidden" name="metodo_pago" id="input-metodo-pago" value="efectivo">

<div class="pasos">
    <div class="paso activo" data-paso-indicador="1"><span class="num-paso">1</span> Bomba</div>
    <div class="paso" data-paso-indicador="2"><span class="num-paso">2</span> Manguera</div>
    <div class="paso" data-paso-indicador="3"><span class="num-paso">3</span> Modalidad</div>
    <div class="paso" data-paso-indicador="4"><span class="num-paso">4</span> Monto y cobro</div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">

        <!-- PASO 1: BOMBA -->
        <div class="bloque-paso" data-paso="1">
            <div class="bloque-paso__titulo">Paso 1 &middot; Selecciona la bomba</div>
            <div class="grilla-teclas" id="grilla-bombas">
                <?php foreach ($bombas as $b): ?>
                <button class="tecla" type="button"
                        data-bomba-id="<?= $b['id'] ?>"
                        <?= $b['estado'] !== 'activa' ? 'disabled' : '' ?>>
                    <div class="tecla__titulo">Bomba <?= $b['numero'] ?></div>
                    <div class="tecla__meta">
                        <?= count($b['mangueras']) ?> manguera(s) &middot;
                        <?= $b['estado'] === 'activa' ? 'Activa' : 'Mantenimiento' ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PASO 2: MANGUERA (una sub-grilla por bomba; se muestra solo la que corresponde) -->
        <div class="bloque-paso" data-paso="2" data-oculto="1">
            <div class="bloque-paso__titulo">Paso 2 &middot; Selecciona la manguera</div>
            <?php foreach ($bombas as $b): ?>
            <div class="grilla-teclas grupo-mangueras" data-mangueras-de-bomba="<?= $b['id'] ?>" style="display:none; margin-bottom:12px;">
                <?php foreach ($b['mangueras'] as $m): ?>
                <button class="tecla" type="button"
                        data-manguera-id="<?= $m['id'] ?>"
                        data-combustible="<?= htmlspecialchars($m['combustible']) ?>"
                        data-estado="<?= $m['estado'] ?>"
                        <?= $m['estado'] !== 'disponible' ? 'disabled' : '' ?>>
                    <div class="tecla__titulo"><?= htmlspecialchars($m['combustible']) ?></div>
                    <div class="tecla__meta">
                        Manguera <?= htmlspecialchars($m['color']) ?> &middot;
                        <?= $m['estado'] === 'disponible' ? 'Disponible' : ($m['estado'] === 'en_uso' ? 'En uso' : 'Bloqueada') ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <button class="btn" type="button" data-volver="1">&larr; Cambiar bomba</button>
        </div>

        <!-- PASO 3: MODALIDAD -->
        <div class="bloque-paso" data-paso="3" data-oculto="1">
            <div class="bloque-paso__titulo">Paso 3 &middot; Modalidad de venta</div>
            <div class="chips" id="chips-modalidad">
                <?php foreach ($modalidades as $m): ?>
                <button class="chip" type="button" data-modalidad="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></button>
                <?php endforeach; ?>
            </div>
            <p id="ayuda-modalidad" style="color:var(--c-muted); font-size:0.82rem;">Elige como el cliente quiere cargar.</p>
            <button class="btn" type="button" data-volver="2">&larr; Cambiar manguera</button>
        </div>

        <!-- PASO 4: MONTO / LITROS Y COBRO -->
        <div class="bloque-paso" data-paso="4" data-oculto="1">
            <div class="bloque-paso__titulo">Paso 4 &middot; Ingresa el valor y cobra</div>
            <div style="display:flex; gap:32px; flex-wrap:wrap;">
                <div class="teclado-numerico" id="teclado-despacho">
                    <button type="button" data-tecla="1">1</button>
                    <button type="button" data-tecla="2">2</button>
                    <button type="button" data-tecla="3">3</button>
                    <button type="button" data-tecla="4">4</button>
                    <button type="button" data-tecla="5">5</button>
                    <button type="button" data-tecla="6">6</button>
                    <button type="button" data-tecla="7">7</button>
                    <button type="button" data-tecla="8">8</button>
                    <button type="button" data-tecla="9">9</button>
                    <button type="button" data-tecla=".">.</button>
                    <button type="button" data-tecla="0">0</button>
                    <button type="button" data-tecla="borrar">&larr;</button>
                    <button type="button" class="doble" id="btn-iniciar-despacho">Iniciar despacho</button>
                </div>

                <div style="flex:1; min-width:240px;">
                    <div class="visor" style="margin-bottom:12px;">
                        <div class="visor__etiqueta" id="etiqueta-entrada">Monto a cargar</div>
                        <div class="visor__digitos"><span id="valor-entrada">0.00</span></div>
                    </div>

                    <div class="linea-ticket">
                        <span class="linea-ticket__etiqueta">Combustible</span>
                        <span class="linea-ticket__relleno"></span>
                        <span class="linea-ticket__valor" id="ticket-combustible">&mdash;</span>
                    </div>
                    <div class="linea-ticket">
                        <span class="linea-ticket__etiqueta">Precio por galon</span>
                        <span class="linea-ticket__relleno"></span>
                        <span class="linea-ticket__valor" id="ticket-precio">&mdash;</span>
                    </div>
                    <div class="linea-ticket">
                        <span class="linea-ticket__etiqueta">Galones estimados</span>
                        <span class="linea-ticket__relleno"></span>
                        <span class="linea-ticket__valor" id="ticket-galones">0.00</span>
                    </div>
                    <div class="linea-ticket linea-ticket--total">
                        <span class="linea-ticket__etiqueta">Total</span>
                        <span class="linea-ticket__relleno"></span>
                        <span class="linea-ticket__valor" id="ticket-total">$0.00</span>
                    </div>

                    <div class="bloque-paso__titulo" style="margin-top:16px;">Metodo de pago</div>
                    <div class="chips" id="chips-pago-pista">
                        <button class="chip activo" type="button" data-metodo-pago="efectivo">Efectivo</button>
                        <button class="chip" type="button" data-metodo-pago="tarjeta">Tarjeta</button>
                        <button class="chip" type="button" data-metodo-pago="mixto">Mixto</button>
                    </div>
                </div>
            </div>
            <button class="btn" type="button" data-volver="3" style="margin-top:12px;">&larr; Cambiar modalidad</button>
        </div>

    </div>
</div>
</form>

<div class="visor" id="panel-arduino">
    <div class="visor__etiqueta">Simulacion del panel fisico (Arduino &middot; LCD 16x2)</div>
    <div class="visor__digitos" id="pantalla-lcd" style="font-size:1.3rem;">Surtidor listo</div>
    <div style="margin-top:8px; display:flex; gap:16px;">
        <span class="estado estado--normal" id="led-verde"><span class="estado__marca"></span> Disponible</span>
        <span class="estado estado--advertencia" id="led-amarillo" style="opacity:0.3;"><span class="estado__marca"></span> Despachando</span>
        <span class="estado estado--alerta" id="led-rojo" style="opacity:0.3;"><span class="estado__marca"></span> Alerta</span>
    </div>
</div>

<script>
    window.DATOS_DESPACHO = {
        bombas: <?= json_encode($bombas) ?>,
        precios: <?= json_encode($precios) ?>
    };
</script>
<script src="assets/js/pos_pista.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
