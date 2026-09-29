<?php
require_once __DIR__ . '/../../controller/DespachoController.php';
require_once __DIR__ . '/../../controller/TurnoController.php';

$tituloPagina    = 'Despacho en pista';
$subtituloPagina = 'Turno activo &middot; caja de pista';
$vistaActiva     = 'pos_pista';
require __DIR__ . '/../layout/header.php';

$turno = TurnoController::obtenerTurnoAbierto('pista');
?>
<link rel="stylesheet" href="assets/css/pos_pista.css">

<div class="pp-page">
    <span class="pp-page__esquina pp-page__esquina--tl"></span>
    <span class="pp-page__esquina pp-page__esquina--tr"></span>
    <span class="pp-page__esquina pp-page__esquina--bl"></span>
    <span class="pp-page__esquina pp-page__esquina--br"></span>

    <?php if ($turno === null): ?>
    <div class="pp-apertura">
        <div class="pp-apertura__icono">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22V4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v18"/><path d="M3 10h8"/><path d="M15 22V9l4-2v13a2 2 0 0 0 2 2"/><circle cx="9" cy="15" r="1"/></svg>
        </div>
        <h2 class="pp-apertura__titulo">Apertura de turno</h2>
        <p class="pp-apertura__texto">Antes de empezar a despachar, cuenta el efectivo que recibes como fondo de caja y confirma el monto. Tambien se toma la lectura inicial de cada manguera activa, para poder conciliar el turno al cerrarlo.</p>

        <form class="pp-apertura__form" method="post" action="index.php?accion=abrir_turno_pista" id="form-abrir-turno">
            <label for="monto_inicial">Fondo inicial recibido</label>
            <div class="pp-apertura__campo">
                <span>$</span>
                <input type="text" name="monto_inicial" id="monto_inicial" inputmode="decimal"
                       value="<?= number_format(TurnoController::montoInicialSugerido(), 2, '.', '') ?>" autocomplete="off" required>
            </div>
            <p class="pp-apertura__sugerencia">Sugerido por politica: $<?= number_format(TurnoController::montoInicialSugerido(), 2) ?>. Cambialo si el fondo que recibiste es distinto.</p>
            <button class="pp-btn-primario" type="submit">Abrir turno y empezar a despachar</button>
        </form>
    </div>
    <?php else:
    $bombas      = DespachoController::bombas();
    $modalidades = DespachoController::modalidadesVenta();
    $precios     = DespachoController::precioVigente();
    $ultimoTicket = Sesion::leerUltimoTicket();
    ?>

    <div class="pp-turno">
        <div class="pp-turno__bloque">
            <div class="pp-turno__etiqueta">Turno activo</div>
            <div class="pp-turno__valor">#<?= (int) $turno['id_turno'] ?></div>
        </div>
        <div class="pp-turno__bloque">
            <div class="pp-turno__etiqueta">Horario</div>
            <div class="pp-turno__valor pp-turno__valor--mono"><?= htmlspecialchars($turno['hora_inicio']) ?> &mdash; <?= htmlspecialchars($turno['hora_prevista']) ?></div>
        </div>
        <div class="pp-turno__bloque">
            <div class="pp-turno__etiqueta">Fondo inicial</div>
            <div class="pp-turno__valor pp-turno__valor--mono">$<?= number_format($turno['monto_inicial'], 2) ?></div>
        </div>
        <div class="pp-turno__bloque pp-turno__bloque--estado">
            <span class="pp-badge pp-badge--normal"><span></span> <?= htmlspecialchars(ucfirst((string) $turno['estado'])) ?></span>
            <a class="pp-turno__enlace" href="index.php?vista=cierre_turno">Cerrar turno &rarr;</a>
        </div>
    </div>

    <div class="pp-pasos" id="pp-pasos">
        <div class="pp-paso activo" data-paso-indicador="1"><span class="pp-paso__num">1</span> Bomba</div>
        <div class="pp-paso" data-paso-indicador="2"><span class="pp-paso__num">2</span> Manguera</div>
        <div class="pp-paso" data-paso-indicador="3"><span class="pp-paso__num">3</span> Modalidad</div>
        <div class="pp-paso" data-paso-indicador="4"><span class="pp-paso__num">4</span> Monto y cobro</div>
    </div>

    <form id="form-despacho" method="post" action="index.php?accion=despacho">
        <input type="hidden" name="id_manguera" id="input-id-manguera">
        <input type="hidden" name="modalidad" id="input-modalidad">
        <input type="hidden" name="valor_entrada" id="input-valor-entrada">
        <input type="hidden" name="metodo_pago" id="input-metodo-pago" value="efectivo">
        <input type="hidden" name="monto_recibido" id="input-monto-recibido">

        <div class="pp-panel">

            <!-- PASO 1: BOMBA -->
            <div class="pp-bloque-paso" data-paso="1">
                <div class="pp-bloque-paso__titulo">Selecciona la bomba</div>
                <div class="pp-grilla" id="grilla-bombas">
                    <?php foreach ($bombas as $b): ?>
                    <button class="pp-tecla" type="button"
                            data-bomba-id="<?= (int) $b['id'] ?>"
                            <?= $b['estado'] !== 'activa' ? 'disabled' : '' ?>>
                        <div class="pp-tecla__titulo">Bomba <?= (int) $b['numero'] ?></div>
                        <div class="pp-tecla__meta">
                            <?= count($b['mangueras']) ?> manguera(s) &middot;
                            <?= $b['estado'] === 'activa' ? 'Activa' : 'Mantenimiento' ?>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PASO 2: MANGUERA -->
            <div class="pp-bloque-paso" data-paso="2" hidden>
                <div class="pp-bloque-paso__titulo">Selecciona la manguera</div>
                <?php foreach ($bombas as $b): ?>
                <div class="pp-grilla pp-grupo-mangueras" data-mangueras-de-bomba="<?= (int) $b['id'] ?>" hidden>
                    <?php foreach ($b['mangueras'] as $m): ?>
                    <button class="pp-tecla" type="button"
                            data-manguera-id="<?= (int) $m['id'] ?>"
                            data-combustible="<?= htmlspecialchars($m['combustible']) ?>"
                            data-estado="<?= htmlspecialchars($m['estado']) ?>"
                            <?= $m['estado'] !== 'disponible' ? 'disabled' : '' ?>>
                        <div class="pp-tecla__titulo"><?= htmlspecialchars($m['combustible']) ?></div>
                        <div class="pp-tecla__meta">
                            Manguera <?= htmlspecialchars($m['color']) ?> &middot;
                            <?= $m['estado'] === 'disponible' ? 'Disponible' : 'Bloqueada' ?>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                <button class="pp-btn-volver" type="button" data-volver="1">&larr; Cambiar bomba</button>
            </div>

            <!-- PASO 3: MODALIDAD -->
            <div class="pp-bloque-paso" data-paso="3" hidden>
                <div class="pp-bloque-paso__titulo">Modalidad de venta</div>
                <div class="pp-chips" id="chips-modalidad">
                    <?php foreach ($modalidades as $m): ?>
                    <button class="pp-chip" type="button" data-modalidad="<?= htmlspecialchars($m['id']) ?>" title="<?= htmlspecialchars($m['ayuda']) ?>"><?= htmlspecialchars($m['nombre']) ?></button>
                    <?php endforeach; ?>
                </div>
                <p class="pp-ayuda" id="ayuda-modalidad">Elige como el cliente quiere cargar.</p>
                <button class="pp-btn-volver" type="button" data-volver="2">&larr; Cambiar manguera</button>
            </div>

            <!-- PASO 4: MONTO / LITROS Y COBRO -->
            <div class="pp-bloque-paso" data-paso="4" hidden>
                <div class="pp-bloque-paso__titulo">Ingresa el valor y cobra</div>
                <div class="pp-paso4">
                    <div class="pp-teclado" id="teclado-despacho">
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
                        <button type="button" class="pp-teclado__iniciar" id="btn-iniciar-despacho">Iniciar despacho</button>
                    </div>

                    <div class="pp-resumen">
                        <div class="pp-visor">
                            <div class="pp-visor__etiqueta" id="etiqueta-entrada">Monto a cargar</div>
                            <div class="pp-visor__digitos"><span id="valor-entrada">0.00</span></div>
                        </div>

                        <div class="pp-linea">
                            <span>Combustible</span>
                            <span id="ticket-combustible">&mdash;</span>
                        </div>
                        <div class="pp-linea">
                            <span>Precio por galon</span>
                            <span id="ticket-precio">&mdash;</span>
                        </div>
                        <div class="pp-linea">
                            <span>Galones estimados</span>
                            <span id="ticket-galones">0.00</span>
                        </div>
                        <div class="pp-linea pp-linea--total">
                            <span>Total</span>
                            <span id="ticket-total">$0.00</span>
                        </div>

                        <div class="pp-seccion">Metodo de pago</div>
                        <div class="pp-chips" id="chips-pago-pista">
                            <button class="pp-chip activo" type="button" data-metodo-pago="efectivo">Efectivo</button>
                            <button class="pp-chip" type="button" data-metodo-pago="tarjeta">Tarjeta</button>
                            <button class="pp-chip" type="button" data-metodo-pago="mixto">Mixto</button>
                        </div>

                        <div class="pp-campo-efectivo" id="bloque-efectivo" hidden>
                            <label for="monto-recibido">Efectivo recibido</label>
                            <input type="text" id="monto-recibido" inputmode="decimal" placeholder="0.00">
                            <div class="pp-campo-efectivo__cambio">
                                <span>Cambio a entregar</span>
                                <span id="monto-cambio">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="pp-btn-volver" type="button" data-volver="3">&larr; Cambiar modalidad</button>
            </div>

        </div>
    </form>

    <div class="pp-arduino">
        <div class="pp-arduino__etiqueta">Simulacion del panel fisico &middot; Arduino (LCD 16x2)</div>
        <div class="pp-arduino__lcd" id="pantalla-lcd">Surtidor listo</div>
        <div class="pp-arduino__leds">
            <span class="pp-led pp-led--verde activo" id="led-verde"><span></span> Disponible</span>
            <span class="pp-led pp-led--amarillo" id="led-amarillo"><span></span> Despachando</span>
            <span class="pp-led pp-led--rojo" id="led-rojo"><span></span> Alerta</span>
        </div>
    </div>

    <div class="pp-recibo-overlay" id="pp-recibo-overlay" hidden>
        <div class="pp-recibo">
            <button class="pp-recibo__cerrar" id="pp-recibo-cerrar" type="button" aria-label="Cerrar">&times;</button>
            <div class="pp-recibo__contenido" id="pp-recibo-contenido"></div>
            <div class="pp-recibo__acciones">
                <button class="pp-btn-secundario" id="pp-recibo-cerrar-btn" type="button">Cerrar</button>
                <button class="pp-btn-primario pp-btn-imprimir" id="pp-recibo-imprimir" type="button">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    window.DATOS_DESPACHO = {
        bombas: <?= isset($bombas) ? json_encode($bombas) : '[]' ?>,
        precios: <?= isset($precios) ? json_encode($precios) : '{}' ?>
    };
    window.DATOS_ULTIMO_TICKET = <?= isset($ultimoTicket) && $ultimoTicket ? json_encode($ultimoTicket) : 'null' ?>;
    window.NOMBRE_NEGOCIO = <?= json_encode(NOMBRE_SISTEMA) ?>;
</script>
<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/pos_pista.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
