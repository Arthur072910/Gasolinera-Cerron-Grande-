/**
 * pos_tienda.js
 * -----------------------------------------------------------------------
 * Interactividad de la pantalla del Cajero: filtro por categoria,
 * busqueda de productos, carrito (agregar/quitar/cantidad) y seleccion
 * de metodo de pago / tipo de comprobante.
 *
 * El carrito vive en memoria del navegador mientras se arma; al pulsar
 * "Cobrar" se serializa a JSON y el formulario #form-venta-tienda se
 * envia a index.php?accion=venta_tienda, que inserta en `ventas` y
 * `detalle_ventas_tienda` y descuenta el stock real.
 * -----------------------------------------------------------------------
 */
(function () {
    const carrito = {}; // { idProducto: { nombre, precio, cantidad } }

    // ---- Filtro por categoria ----
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
            btn.style.display = visible ? '' : 'none';
            if (visible) visibles++;
        });

        sinResultados.style.display = visibles === 0 ? 'block' : 'none';
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

    function renderizarCarrito() {
        const ids = Object.keys(carrito);
        listaCarrito.innerHTML = '';

        if (ids.length === 0) {
            listaCarrito.appendChild(carritoVacioMsg);
            carritoTotalEl.textContent = '$0.00';
            return;
        }

        let total = 0;
        ids.forEach((id) => {
            const item = carrito[id];
            const subtotal = item.precio * item.cantidad;
            total += subtotal;

            const linea = document.createElement('div');
            linea.className = 'linea-carrito';
            linea.innerHTML = `
                <span class="linea-carrito__nombre">${item.nombre}<br><small style="color:var(--c-muted)">$${item.precio.toFixed(2)} c/u</small></span>
                <span class="stepper">
                    <button type="button" data-accion="restar" data-id="${id}">-</button>
                    <span>${item.cantidad}</span>
                    <button type="button" data-accion="sumar" data-id="${id}">+</button>
                </span>
                <span class="linea-ticket__valor" style="width:64px; text-align:right;">$${subtotal.toFixed(2)}</span>
                <button class="linea-carrito__quitar" type="button" data-accion="quitar" data-id="${id}">Quitar</button>
            `;
            listaCarrito.appendChild(linea);
        });

        carritoTotalEl.textContent = `$${total.toFixed(2)}`;
    }

    productos.forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.productoId;
            if (!carrito[id]) {
                carrito[id] = { nombre: btn.dataset.nombre, precio: parseFloat(btn.dataset.precio), cantidad: 0 };
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

        if (accion === 'sumar') carrito[id].cantidad += 1;
        if (accion === 'restar') {
            carrito[id].cantidad -= 1;
            if (carrito[id].cantidad <= 0) delete carrito[id];
        }
        if (accion === 'quitar') delete carrito[id];

        renderizarCarrito();
    });

    // ---- Metodo de pago / comprobante (chips de seleccion unica) ----
    function activarChipUnico(selector) {
        document.querySelectorAll(selector).forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll(selector).forEach((c) => c.classList.remove('activo'));
                chip.classList.add('activo');

                if (selector === '#chips-comprobante .chip') {
                    const camposFiscales = document.getElementById('campos-fiscales');
                    const esFiscal = chip.dataset.comprobante === 'factura' || chip.dataset.comprobante === 'ccf';
                    camposFiscales.style.display = esFiscal ? 'block' : 'none';
                }
            });
        });
    }
    activarChipUnico('#chips-pago .chip');
    activarChipUnico('#chips-comprobante .chip');

    // ---- Cobrar ----
    const btnCobrar = document.getElementById('btn-cobrar');
    const formulario = document.getElementById('form-venta-tienda');

    btnCobrar.addEventListener('click', () => {
        if (Object.keys(carrito).length === 0) {
            alert('Agrega al menos un producto antes de cobrar.');
            return;
        }
        const metodoPago = document.querySelector('#chips-pago .chip.activo');
        if (!metodoPago) {
            alert('Selecciona un metodo de pago.');
            return;
        }
        const comprobante = document.querySelector('#chips-comprobante .chip.activo');

        const carritoJson = Object.keys(carrito).map((id) => ({
            id_producto: Number(id),
            cantidad: carrito[id].cantidad,
        }));

        document.getElementById('input-carrito').value = JSON.stringify(carritoJson);
        document.getElementById('input-metodo-pago').value = metodoPago.dataset.pago;
        document.getElementById('input-tipo-comprobante').value = comprobante ? comprobante.dataset.comprobante : 'ticket';

        formulario.submit();
    });

    console.info('[pos_tienda.js] Carrito conectado a index.php?accion=venta_tienda.');
})();
