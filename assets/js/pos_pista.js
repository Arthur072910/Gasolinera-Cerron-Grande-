/**
 * pos_pista.js
 * -----------------------------------------------------------------------
 * Interactividad del asistente de 4 pasos (Bomba -> Manguera -> Modalidad
 * -> Monto/cobro) del Despachador, mas el recibo en pantalla tras cobrar.
 *
 * El panel "Simulacion del panel fisico (Arduino)" solo actualiza texto
 * y LEDs en el navegador; esta separado a proposito (todo pasa por
 * actualizarPanelArduino()) para que cuando llegue el hardware real (la
 * bomba de agua sumergible que se usara como analogo del surtidor) sea
 * facil reemplazar estas llamadas por datos que vengan de verdad del
 * Arduino via serial/websocket, sin tocar el resto del flujo.
 *
 * Al pulsar "Iniciar despacho" el formulario #form-despacho se envia a
 * index.php?accion=despacho, que revalida todo en el servidor (precio,
 * stock del tanque, tope de monto) e inserta en `ventas` y
 * `detalle_ventas_combustible`, incrementando el contador de la manguera.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#212f39',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
    };

    function avisar(icono, titulo) {
        if (typeof Swal === 'undefined') return;
        Swal.fire(Object.assign({}, temaSwal, {
            icon: icono,
            title: titulo,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: icono === 'error' ? 4000 : 2600,
            timerProgressBar: true,
        }));
    }

    try {
        const banner = document.querySelector('.banner-aviso');
        if (banner && banner.classList.contains('mostrar') && typeof Swal !== 'undefined') {
            const esError = banner.classList.contains('banner-aviso--error');
            avisar(esError ? 'error' : 'success', banner.textContent.trim());
        }
    } catch (error) {
        console.error('[pos_pista.js] No se pudo mostrar el aviso:', error);
    }

    // Pantalla de apertura de turno (sin turno abierto todavia): no hay
    // asistente de despacho que conectar.
    if (!document.getElementById('form-despacho')) {
        return;
    }

    const datos = window.DATOS_DESPACHO || { bombas: [], precios: {} };

    const estado = {
        bombaId: null,
        mangueraId: null,
        combustible: null,
        modalidad: null,
        entrada: '0',
        metodoPago: 'efectivo',
    };

    function actualizarPanelArduino(texto, modo) {
        const lcd = document.getElementById('pantalla-lcd');
        if (lcd) lcd.textContent = texto;

        const leds = { verde: document.getElementById('led-verde'), amarillo: document.getElementById('led-amarillo'), rojo: document.getElementById('led-rojo') };
        Object.values(leds).forEach((led) => { if (led) led.classList.remove('activo'); });
        if (leds[modo]) leds[modo].classList.add('activo');
    }

    // ---- Paso 1: bombas ----
    document.querySelectorAll('#grilla-bombas [data-bomba-id]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            estado.bombaId = btn.dataset.bombaId;
            estado.mangueraId = null;
            estado.combustible = null;

            document.querySelectorAll('.pp-grupo-mangueras').forEach((grupo) => {
                grupo.hidden = grupo.dataset.manguerasDeBomba !== estado.bombaId;
            });

            irAPaso(2);
            actualizarPanelArduino(`Bomba ${estado.bombaId} seleccionada`, 'verde');
        });
    });

    // ---- Paso 2: mangueras ----
    document.querySelectorAll('[data-manguera-id]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            estado.mangueraId = btn.dataset.mangueraId;
            estado.combustible = btn.dataset.combustible;
            irAPaso(3);
            actualizarPanelArduino(`Manguera lista: ${estado.combustible}`, 'verde');
        });
    });

    document.querySelectorAll('[data-volver]').forEach((btn) => {
        btn.addEventListener('click', () => irAPaso(Number(btn.dataset.volver)));
    });

    function irAPaso(numero) {
        document.querySelectorAll('.pp-bloque-paso').forEach((el) => {
            el.hidden = el.dataset.paso !== String(numero);
        });
        document.querySelectorAll('#pp-pasos [data-paso-indicador]').forEach((el) => {
            const n = Number(el.dataset.pasoIndicador);
            el.classList.remove('activo', 'completado');
            if (n === numero) el.classList.add('activo');
            else if (n < numero) el.classList.add('completado');
        });
    }

    // ---- Paso 3: modalidad ----
    document.querySelectorAll('#chips-modalidad [data-modalidad]').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#chips-modalidad .pp-chip').forEach((c) => c.classList.remove('activo'));
            chip.classList.add('activo');
            estado.modalidad = chip.dataset.modalidad;
            estado.entrada = '0';

            const etiqueta = document.getElementById('etiqueta-entrada');
            if (etiqueta) {
                etiqueta.textContent = estado.modalidad === 'litros' ? 'Galones a cargar'
                    : estado.modalidad === 'lleno' ? 'Galones despachados (tanque lleno)'
                    : 'Monto a cargar';
            }

            document.getElementById('ticket-combustible').textContent = estado.combustible || '—';
            const precio = datos.precios[estado.combustible];
            document.getElementById('ticket-precio').textContent = precio ? `$${precio.toFixed(3)}` : '—';

            irAPaso(4);
            recalcularTicket();
        });
    });

    // ---- Paso 4: teclado numerico ----
    function calcularGalonesYTotal() {
        const precio = datos.precios[estado.combustible] || 0;
        const valor = parseFloat(estado.entrada) || 0;
        let galones = 0;
        let total = 0;

        if (estado.modalidad === 'litros' || estado.modalidad === 'lleno') {
            galones = valor;
            total = galones * precio;
        } else {
            total = valor;
            galones = precio > 0 ? total / precio : 0;
        }
        return { valor, galones, total };
    }

    function recalcularTicket() {
        const { valor, galones, total } = calcularGalonesYTotal();
        document.getElementById('valor-entrada').textContent = valor.toFixed(2);
        document.getElementById('ticket-galones').textContent = galones.toFixed(2);
        document.getElementById('ticket-total').textContent = `$${total.toFixed(2)}`;
        actualizarCambio();
    }

    document.querySelectorAll('#teclado-despacho [data-tecla]').forEach((tecla) => {
        tecla.addEventListener('click', () => {
            const valor = tecla.dataset.tecla;
            if (valor === 'borrar') {
                estado.entrada = estado.entrada.length > 1 ? estado.entrada.slice(0, -1) : '0';
            } else if (valor === '.' && estado.entrada.includes('.')) {
                return;
            } else if (estado.entrada === '0' && valor !== '.') {
                estado.entrada = valor;
            } else {
                estado.entrada += valor;
            }
            recalcularTicket();
        });
    });

    // ---- Metodo de pago + efectivo/cambio ----
    const bloqueEfectivo = document.getElementById('bloque-efectivo');
    const inputRecibido = document.getElementById('monto-recibido');
    const montoCambioEl = document.getElementById('monto-cambio');

    function actualizarCambio() {
        if (!bloqueEfectivo || bloqueEfectivo.hidden) return;
        const { total } = calcularGalonesYTotal();
        const recibido = parseFloat(inputRecibido.value) || 0;
        const cambio = recibido - total;
        montoCambioEl.textContent = `$${Math.max(0, cambio).toFixed(2)}`;
        montoCambioEl.classList.toggle('pp-campo-efectivo__cambio--negativo', cambio < 0);
    }
    if (inputRecibido) inputRecibido.addEventListener('input', actualizarCambio);

    document.querySelectorAll('#chips-pago-pista .pp-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#chips-pago-pista .pp-chip').forEach((c) => c.classList.remove('activo'));
            chip.classList.add('activo');
            estado.metodoPago = chip.dataset.metodoPago;
            if (bloqueEfectivo) bloqueEfectivo.hidden = estado.metodoPago !== 'efectivo';
            actualizarCambio();
        });
    });

    // ---- Confirmar despacho (simulacion Arduino + envio real al servidor) ----
    const btnIniciar = document.getElementById('btn-iniciar-despacho');
    const formulario = document.getElementById('form-despacho');

    if (btnIniciar && formulario) {
        btnIniciar.addEventListener('click', () => {
            if (!estado.mangueraId || !estado.modalidad) {
                avisar('warning', 'Selecciona bomba, manguera y modalidad antes de despachar.');
                return;
            }
            const { galones, total } = calcularGalonesYTotal();
            if (galones <= 0 || total <= 0) {
                avisar('warning', 'Ingresa un monto o cantidad de galones mayor a cero.');
                return;
            }

            let montoRecibido = '';
            if (estado.metodoPago === 'efectivo') {
                const recibido = parseFloat(inputRecibido.value) || 0;
                if (recibido < total) {
                    avisar('warning', 'El efectivo recibido es menor al total del despacho.');
                    return;
                }
                montoRecibido = recibido;
            }

            actualizarPanelArduino(`Despachando ${estado.combustible}...`, 'amarillo');
            btnIniciar.disabled = true;

            setTimeout(() => {
                actualizarPanelArduino('Despacho finalizado, guardando...', 'amarillo');
                document.getElementById('input-id-manguera').value = estado.mangueraId;
                document.getElementById('input-modalidad').value = estado.modalidad;
                document.getElementById('input-valor-entrada').value = estado.entrada;
                document.getElementById('input-metodo-pago').value = estado.metodoPago;
                document.getElementById('input-monto-recibido').value = montoRecibido;
                formulario.submit();
            }, 1400);
        });
    }

    // ---- Recibo en pantalla del ultimo despacho cobrado ----
    try {
        mostrarUltimoRecibo();
    } catch (error) {
        console.error('[pos_pista.js] No se pudo mostrar el recibo:', error);
    }

    function mostrarUltimoRecibo() {
        const ticket = window.DATOS_ULTIMO_TICKET;
        const overlay = document.getElementById('pp-recibo-overlay');
        if (!ticket || !overlay) return;

        const etiquetasPago = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', mixto: 'Mixto' };

        let bloqueEfectivoRecibo = '';
        if (ticket.metodo_pago === 'efectivo' && ticket.recibido !== null && ticket.recibido !== undefined) {
            bloqueEfectivoRecibo = `
                <div class="pp-recibo__linea"><span>Efectivo recibido</span><span>$${Number(ticket.recibido).toFixed(2)}</span></div>
                <div class="pp-recibo__linea pp-recibo__linea--cambio"><span>Cambio entregado</span><span>$${Number(ticket.cambio).toFixed(2)}</span></div>
            `;
        }

        document.getElementById('pp-recibo-contenido').innerHTML = `
            <div class="pp-recibo__marca">${window.NOMBRE_NEGOCIO || 'El Cerron Grande'}</div>
            <div class="pp-recibo__sub">Despacho de combustible</div>
            <div class="pp-recibo__numero">${ticket.numero_comprobante}</div>
            <div class="pp-recibo__meta">
                <span>${ticket.fecha}</span>
                <span>Despachador: ${ticket.usuario}</span>
            </div>
            <div class="pp-recibo__separador"></div>
            <div class="pp-recibo__linea"><span>Combustible</span><span>${ticket.combustible}</span></div>
            <div class="pp-recibo__linea"><span>Galones</span><span>${Number(ticket.galones).toFixed(3)} gal</span></div>
            <div class="pp-recibo__linea"><span>Precio por galon</span><span>$${Number(ticket.precio).toFixed(3)}</span></div>
            <div class="pp-recibo__separador"></div>
            <div class="pp-recibo__linea pp-recibo__linea--total"><span>TOTAL</span><span>$${Number(ticket.total).toFixed(2)}</span></div>
            <div class="pp-recibo__linea"><span>Metodo de pago</span><span>${etiquetasPago[ticket.metodo_pago] || ticket.metodo_pago}</span></div>
            ${bloqueEfectivoRecibo}
            <div class="pp-recibo__gracias">Gracias por su compra</div>
        `;

        overlay.hidden = false;

        const cerrar = () => { overlay.hidden = true; };
        document.getElementById('pp-recibo-cerrar').addEventListener('click', cerrar);
        document.getElementById('pp-recibo-cerrar-btn').addEventListener('click', cerrar);
        document.getElementById('pp-recibo-imprimir').addEventListener('click', () => window.print());
        overlay.addEventListener('click', (evento) => { if (evento.target === overlay) cerrar(); });
        document.addEventListener('keydown', (evento) => { if (evento.key === 'Escape' && !overlay.hidden) cerrar(); });
    }
})();
