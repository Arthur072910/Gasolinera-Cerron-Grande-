/**
 * pos_pista.js
 * -----------------------------------------------------------------------
 * Interactividad del asistente de 4 pasos (Bomba -> Manguera -> Modalidad
 * -> Monto/cobro) para la pantalla del Despachador.
 *
 * La animacion del LCD/relee es solo la simulacion visual del panel
 * fisico; al terminarla, el formulario #form-despacho se envia de
 * verdad a index.php?accion=despacho, que registra la venta contra
 * `ventas` / `detalle_ventas_combustible` y actualiza el contador de
 * la manguera.
 * -----------------------------------------------------------------------
 */
(function () {
    const datos = window.DATOS_DESPACHO || { bombas: [], precios: {} };

    const estado = {
        bombaId: null,
        mangueraId: null,
        combustible: null,
        modalidad: null,
        entrada: '0',
    };

    const nombresModalidad = { monto: 'Monto fijo', litros: 'Litros fijos', lleno: 'Tanque lleno' };

    function irAPaso(numero) {
        document.querySelectorAll('.bloque-paso').forEach((el) => {
            el.dataset.oculto = el.dataset.paso === String(numero) ? '0' : '1';
        });
        document.querySelectorAll('[data-paso-indicador]').forEach((el) => {
            const n = Number(el.dataset.pasoIndicador);
            el.classList.remove('activo', 'completado');
            if (n === numero) el.classList.add('activo');
            else if (n < numero) el.classList.add('completado');
        });
    }

    function actualizarResumen() {
        const resumen = document.getElementById('resumen-seleccion');
        if (!resumen) return;
        if (estado.bombaId && estado.mangueraId) {
            resumen.textContent = `Bomba ${estado.bombaId} / ${estado.combustible}`;
        } else if (estado.bombaId) {
            resumen.textContent = `Bomba ${estado.bombaId} (falta manguera)`;
        } else {
            resumen.textContent = 'ninguna';
        }
    }

    // ---- Paso 1: bombas ----
    document.querySelectorAll('#grilla-bombas [data-bomba-id]').forEach((btn) => {
        btn.addEventListener('click', () => {
            estado.bombaId = btn.dataset.bombaId;
            estado.mangueraId = null;
            estado.combustible = null;

            document.querySelectorAll('.grupo-mangueras').forEach((grupo) => {
                grupo.style.display = grupo.dataset.manguerasDeBomba === estado.bombaId ? 'grid' : 'none';
            });

            actualizarResumen();
            irAPaso(2);

            const lcd = document.getElementById('pantalla-lcd');
            if (lcd) lcd.textContent = `Bomba ${estado.bombaId} seleccionada`;
        });
    });

    // ---- Paso 2: mangueras ----
    document.querySelectorAll('[data-manguera-id]').forEach((btn) => {
        btn.addEventListener('click', () => {
            estado.mangueraId = btn.dataset.mangueraId;
            estado.combustible = btn.dataset.combustible;
            actualizarResumen();
            irAPaso(3);

            const lcd = document.getElementById('pantalla-lcd');
            if (lcd) lcd.textContent = `Manguera lista: ${estado.combustible}`;
        });
    });

    document.querySelectorAll('[data-volver]').forEach((btn) => {
        btn.addEventListener('click', () => irAPaso(Number(btn.dataset.volver)));
    });

    // ---- Paso 3: modalidad ----
    document.querySelectorAll('#chips-modalidad [data-modalidad]').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#chips-modalidad .chip').forEach((c) => c.classList.remove('activo'));
            chip.classList.add('activo');
            estado.modalidad = chip.dataset.modalidad;

            const etiqueta = document.getElementById('etiqueta-entrada');
            if (etiqueta) {
                etiqueta.textContent = estado.modalidad === 'litros' ? 'Galones a cargar'
                    : estado.modalidad === 'lleno' ? 'Tanque lleno (sin monto fijo)'
                    : 'Monto a cargar';
            }

            document.getElementById('ticket-combustible').textContent = estado.combustible || '—';
            const precio = datos.precios[estado.combustible];
            document.getElementById('ticket-precio').textContent = precio ? `$${precio.toFixed(3)}` : '—';

            irAPaso(4);
        });
    });

    // ---- Paso 4: teclado numerico ----
    function recalcularTicket() {
        const precio = datos.precios[estado.combustible] || 0;
        const valor = parseFloat(estado.entrada) || 0;
        let galones = 0;
        let total = 0;

        if (estado.modalidad === 'litros') {
            galones = valor;
            total = galones * precio;
        } else if (estado.modalidad === 'lleno') {
            galones = valor; // en tanque lleno, el valor ingresado se usa solo como referencia visual
            total = galones * precio;
        } else {
            total = valor;
            galones = precio > 0 ? total / precio : 0;
        }

        document.getElementById('valor-entrada').textContent = valor.toFixed(2);
        document.getElementById('ticket-galones').textContent = galones.toFixed(2);
        document.getElementById('ticket-total').textContent = `$${total.toFixed(2)}`;
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

    // ---- Metodo de pago ----
    let metodoPago = 'efectivo';
    document.querySelectorAll('#chips-pago-pista .chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#chips-pago-pista .chip').forEach((c) => c.classList.remove('activo'));
            chip.classList.add('activo');
            metodoPago = chip.dataset.metodoPago;
        });
    });

    // ---- Confirmar despacho (simulacion Arduino + envio real al servidor) ----
    const btnIniciar = document.getElementById('btn-iniciar-despacho');
    const formulario = document.getElementById('form-despacho');
    if (btnIniciar && formulario) {
        btnIniciar.addEventListener('click', () => {
            const lcd = document.getElementById('pantalla-lcd');
            const ledVerde = document.getElementById('led-verde');
            const ledAmarillo = document.getElementById('led-amarillo');

            if (!estado.mangueraId || !estado.modalidad || parseFloat(estado.entrada) <= 0) {
                if (lcd) lcd.textContent = 'Falta seleccionar manguera, modalidad o ingresar un valor';
                return;
            }

            if (ledVerde) ledVerde.style.opacity = '0.3';
            if (ledAmarillo) ledAmarillo.style.opacity = '1';
            if (lcd) lcd.textContent = `Despachando ${estado.combustible}...`;
            btnIniciar.disabled = true;

            setTimeout(() => {
                if (lcd) lcd.textContent = 'Despacho finalizado, guardando...';
                document.getElementById('input-id-manguera').value = estado.mangueraId;
                document.getElementById('input-modalidad').value = estado.modalidad;
                document.getElementById('input-valor-entrada').value = estado.entrada;
                document.getElementById('input-metodo-pago').value = metodoPago;
                formulario.submit();
            }, 1400);
        });
    }

    console.info('[pos_pista.js] Asistente de despacho activo, conectado a index.php?accion=despacho.');
})();
