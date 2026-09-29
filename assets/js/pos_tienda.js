/**
 * pos_tienda.js
 * -----------------------------------------------------------------------
 * Interactividad de la pantalla del Cajero: filtro por categoria,
 * busqueda de productos, carrito (agregar/quitar/cantidad, respetando el
 * stock real de cada producto), calculo de cambio en efectivo, campos
 * fiscales para factura/CCF y aviso de resultado con SweetAlert2.
 *
 * El carrito vive en memoria del navegador mientras se arma; al pulsar
 * "Cobrar" se serializa a JSON y el formulario #form-venta-tienda se
 * envia a index.php?accion=venta_tienda, que revalida todo en el
 * servidor (precio, stock, campos fiscales) e inserta en `ventas` y
 * `detalle_ventas_tienda`, descontando el stock real.
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
        console.error('[pos_tienda.js] No se pudo mostrar el aviso:', error);
    }

    // Pantalla de apertura de caja (sin turno abierto todavia): no existe
    // grilla de productos ni carrito que conectar, asi que no hay nada
    // mas que hacer aqui (el aviso de arriba ya se mostro).
    if (!document.getElementById('form-venta-tienda')) {
        return;
    }

    const carrito = {}; // { idProducto: { nombre, precio, stock, cantidad } }

    // ---- Filtro por categoria + busqueda ----
    const pestanas = document.querySelectorAll('#pestanas-categorias button');
    const productos = document.querySelectorAll('#grilla-productos [data-producto-id]');
    const inputBuscar = document.getElementById('buscador-productos');
    const sinResultados = document.getElementById('sin-resultados');

    function aplicarFiltros() {
        const categoriaActiva = document.querySelector('#pestanas-categorias button.activa').dataset.categoria;
        const texto = (inputBuscar.value || '').toLowerCase().trim();
        let visibles = 0;

        productos.forEach((btn) => {
            const coincideCategoria = categoriaActiva === 'todas' || btn.dataset.categoria === categoriaActiva;
            const coincideTexto = !texto
                || btn.dataset.nombre.toLowerCase().includes(texto)
                || btn.dataset.codigo.toLowerCase().includes(texto);

            const visible = coincideCategoria && coincideTexto;
            btn.hidden = !visible;
            if (visible) visibles++;
        });

        sinResultados.hidden = visibles !== 0;
    }

    pestanas.forEach((btn) => {
        btn.addEventListener('click', () => {
            pestanas.forEach((b) => b.classList.remove('activa'));
            btn.classList.add('activa');
            aplicarFiltros();
        });
    });

    if (inputBuscar) inputBuscar.addEventListener('input', aplicarFiltros);

    // ---- Carrito ----
    const listaCarrito = document.getElementById('lista-carrito');
    const carritoVacioMsg = document.getElementById('carrito-vacio-msg');
    const carritoTotalEl = document.getElementById('carrito-total');

    function calcularTotal() {
        return Object.keys(carrito).reduce((acc, id) => acc + carrito[id].precio * carrito[id].cantidad, 0);
    }

    function renderizarCarrito() {
        const ids = Object.keys(carrito);
        listaCarrito.innerHTML = '';

        if (ids.length === 0) {
            listaCarrito.appendChild(carritoVacioMsg);
            carritoTotalEl.textContent = '$0.00';
            actualizarCambio();
            return;
        }

        ids.forEach((id) => {
            const item = carrito[id];
            const subtotal = item.precio * item.cantidad;

            const linea = document.createElement('div');
            linea.className = 'pt-carrito-linea';
            linea.innerHTML = `
                <div class="pt-carrito-linea__info">
                    <span class="pt-carrito-linea__nombre">${item.nombre}</span>
                    <span class="pt-carrito-linea__precio">$${item.precio.toFixed(2)} c/u</span>
                </div>
                <div class="pt-stepper">
                    <button type="button" data-accion="restar" data-id="${id}">&minus;</button>
                    <span>${item.cantidad}</span>
                    <button type="button" data-accion="sumar" data-id="${id}">+</button>
                </div>
                <span class="pt-carrito-linea__subtotal">$${subtotal.toFixed(2)}</span>
                <button class="pt-carrito-linea__quitar" type="button" data-accion="quitar" data-id="${id}" aria-label="Quitar">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
            `;
            listaCarrito.appendChild(linea);
        });

        carritoTotalEl.textContent = `$${calcularTotal().toFixed(2)}`;
        actualizarCambio();
    }

    productos.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            const id = btn.dataset.productoId;
            const stock = parseInt(btn.dataset.stock, 10) || 0;
            if (!carrito[id]) {
                carrito[id] = { nombre: btn.dataset.nombre, precio: parseFloat(btn.dataset.precio), stock, cantidad: 0 };
            }
            if (carrito[id].cantidad >= stock) {
                avisar('warning', `Solo hay ${stock} unidades de "${btn.dataset.nombre}" en stock.`);
                return;
            }
            carrito[id].cantidad += 1;
            renderizarCarrito();
        });
    });

    listaCarrito.addEventListener('click', (evento) => {
        const boton = evento.target.closest('button[data-accion]');
        if (!boton) return;
        const id = boton.dataset.id;
        const accion = boton.dataset.accion;
        const item = carrito[id];
        if (!item) return;

        if (accion === 'sumar') {
            if (item.cantidad >= item.stock) {
                avisar('warning', `Solo hay ${item.stock} unidades de "${item.nombre}" en stock.`);
                return;
            }
            item.cantidad += 1;
        }
        if (accion === 'restar') {
            item.cantidad -= 1;
            if (item.cantidad <= 0) delete carrito[id];
        }
        if (accion === 'quitar') delete carrito[id];

        renderizarCarrito();
    });

    // ---- Metodo de pago (chip unico) + bloque de efectivo/cambio ----
    const bloqueEfectivo = document.getElementById('bloque-efectivo');
    const inputRecibido = document.getElementById('monto-recibido');
    const montoCambioEl = document.getElementById('monto-cambio');

    function actualizarCambio() {
        if (bloqueEfectivo.hidden) return;
        const recibido = parseFloat(inputRecibido.value) || 0;
        const cambio = recibido - calcularTotal();
        montoCambioEl.textContent = `$${Math.max(0, cambio).toFixed(2)}`;
        montoCambioEl.classList.toggle('pt-campo-efectivo__cambio--negativo', cambio < 0);
    }
    if (inputRecibido) inputRecibido.addEventListener('input', actualizarCambio);

    document.querySelectorAll('#chips-pago .pt-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#chips-pago .pt-chip').forEach((c) => c.classList.remove('activo'));
            chip.classList.add('activo');
            bloqueEfectivo.hidden = chip.dataset.pago !== 'efectivo';
            actualizarCambio();
        });
    });

    // ---- Comprobante (chip unico) + campos fiscales ----
    const camposFiscales = document.getElementById('campos-fiscales');
    document.querySelectorAll('#chips-comprobante .pt-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#chips-comprobante .pt-chip').forEach((c) => c.classList.remove('activo'));
            chip.classList.add('activo');
            const esFiscal = chip.dataset.comprobante === 'factura' || chip.dataset.comprobante === 'ccf';
            camposFiscales.hidden = !esFiscal;
        });
    });

    // ---- Cobrar ----
    const btnCobrar = document.getElementById('btn-cobrar');
    const formulario = document.getElementById('form-venta-tienda');

    btnCobrar.addEventListener('click', () => {
        if (Object.keys(carrito).length === 0) {
            avisar('warning', 'Agrega al menos un producto antes de cobrar.');
            return;
        }
        const metodoPago = document.querySelector('#chips-pago .pt-chip.activo');
        if (!metodoPago) {
            avisar('warning', 'Selecciona un metodo de pago.');
            return;
        }
        const comprobante = document.querySelector('#chips-comprobante .pt-chip.activo');
        const tipoComprobante = comprobante ? comprobante.dataset.comprobante : 'ticket';

        const nombreCliente = document.getElementById('campo-nombre-cliente').value.trim();
        const nitCliente = document.getElementById('campo-nit-cliente').value.trim();
        if ((tipoComprobante === 'factura' || tipoComprobante === 'ccf') && (!nombreCliente || !nitCliente)) {
            avisar('warning', 'Para factura o CCF, completa el nombre y NIT/NRC del cliente.');
            return;
        }

        let montoRecibido = '';
        if (metodoPago.dataset.pago === 'efectivo') {
            const recibido = parseFloat(inputRecibido.value) || 0;
            if (recibido < calcularTotal()) {
                avisar('warning', 'El efectivo recibido es menor al total de la venta.');
                return;
            }
            montoRecibido = recibido;
        }

        const carritoJson = Object.keys(carrito).map((id) => ({
            id_producto: Number(id),
            cantidad: carrito[id].cantidad,
        }));

        document.getElementById('input-carrito').value = JSON.stringify(carritoJson);
        document.getElementById('input-metodo-pago').value = metodoPago.dataset.pago;
        document.getElementById('input-tipo-comprobante').value = tipoComprobante;
        document.getElementById('input-nombre-cliente').value = nombreCliente;
        document.getElementById('input-nit-cliente').value = nitCliente;
        document.getElementById('input-monto-recibido').value = montoRecibido;

        formulario.submit();
    });

    // ---- Recibo en pantalla de la ultima venta cobrada ----
    try {
        mostrarUltimoRecibo();
    } catch (error) {
        console.error('[pos_tienda.js] No se pudo mostrar el recibo:', error);
    }

    function mostrarUltimoRecibo() {
        const ticket = window.DATOS_ULTIMO_TICKET;
        const overlay = document.getElementById('pt-recibo-overlay');
        if (!ticket || !overlay) return;

        const etiquetasComprobante = { ticket: 'Ticket simple', factura: 'Factura de consumidor final', ccf: 'Comprobante de Credito Fiscal' };
        const etiquetasPago = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', mixto: 'Mixto' };

        const filasProductos = ticket.lineas.map((linea) => `
            <div class="pt-recibo__linea">
                <span>${linea.cantidad} x ${linea.nombre}</span>
                <span>$${Number(linea.subtotal).toFixed(2)}</span>
            </div>
        `).join('');

        let bloqueCliente = '';
        if (ticket.nombre_cliente || ticket.nit_cliente) {
            bloqueCliente = `
                <div class="pt-recibo__separador"></div>
                <div class="pt-recibo__linea"><span>Cliente</span><span>${ticket.nombre_cliente || '--'}</span></div>
                <div class="pt-recibo__linea"><span>NIT/NRC</span><span>${ticket.nit_cliente || '--'}</span></div>
            `;
        }

        let bloqueEfectivo = '';
        if (ticket.metodo_pago === 'efectivo' && ticket.recibido !== null && ticket.recibido !== undefined) {
            bloqueEfectivo = `
                <div class="pt-recibo__linea"><span>Efectivo recibido</span><span>$${Number(ticket.recibido).toFixed(2)}</span></div>
                <div class="pt-recibo__linea pt-recibo__linea--cambio"><span>Cambio entregado</span><span>$${Number(ticket.cambio).toFixed(2)}</span></div>
            `;
        }

        document.getElementById('pt-recibo-contenido').innerHTML = `
            <div class="pt-recibo__marca">${window.NOMBRE_NEGOCIO || 'El Cerron Grande'}</div>
            <div class="pt-recibo__sub">${etiquetasComprobante[ticket.tipo_comprobante] || 'Comprobante'}</div>
            <div class="pt-recibo__numero">${ticket.numero_comprobante}</div>
            <div class="pt-recibo__meta">
                <span>${ticket.fecha}</span>
                <span>Cajero: ${ticket.usuario}</span>
            </div>
            <div class="pt-recibo__separador"></div>
            ${filasProductos}
            <div class="pt-recibo__separador"></div>
            <div class="pt-recibo__linea pt-recibo__linea--total"><span>TOTAL</span><span>$${Number(ticket.total).toFixed(2)}</span></div>
            <div class="pt-recibo__linea"><span>Metodo de pago</span><span>${etiquetasPago[ticket.metodo_pago] || ticket.metodo_pago}</span></div>
            ${bloqueEfectivo}
            ${bloqueCliente}
            <div class="pt-recibo__gracias">Gracias por su compra</div>
        `;

        overlay.hidden = false;

        const cerrar = () => { overlay.hidden = true; };
        document.getElementById('pt-recibo-cerrar').addEventListener('click', cerrar);
        document.getElementById('pt-recibo-cerrar-btn').addEventListener('click', cerrar);
        document.getElementById('pt-recibo-imprimir').addEventListener('click', () => window.print());
        overlay.addEventListener('click', (evento) => { if (evento.target === overlay) cerrar(); });
        document.addEventListener('keydown', (evento) => { if (evento.key === 'Escape' && !overlay.hidden) cerrar(); });
    }
})();
