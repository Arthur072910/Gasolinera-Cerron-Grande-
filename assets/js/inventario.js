/**
 * inventario.js
 * -----------------------------------------------------------------------
 * Comportamiento de Administrador > Inventario y tienda:
 *   - Modal reutilizado para "Nuevo producto" / "Editar producto"
 *     (mismo patron que usuarios.js), precargado desde los atributos
 *     data-* del boton "Editar" de cada fila. En modo editar el campo
 *     de stock inicial se oculta (el stock se cambia solo desde el
 *     modal de ajuste, nunca reescribiendo el catalogo).
 *   - Modal separado para "Ajustar stock" (sumar/restar cantidad).
 *   - Confirmacion con SweetAlert2 antes de eliminar un producto.
 *   - Filtro combinado (buscador + categoria) sobre la tabla, y filtro
 *     de tipo sobre el kardex.
 *   - Aviso del resultado de la ultima accion con SweetAlert2 en vez
 *     del banner de texto plano.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#212f39',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
        cancelButtonColor: '#3a4753',
    };

    function avisar(opciones) {
        if (typeof Swal === 'undefined') return;
        Swal.fire(Object.assign({}, temaSwal, opciones));
    }

    // ---- Notificacion del resultado de la ultima accion ----
    const banner = document.querySelector('.banner-aviso');
    if (banner && banner.classList.contains('mostrar')) {
        const esError = banner.classList.contains('banner-aviso--error');
        avisar({
            icon: esError ? 'error' : 'success',
            title: banner.textContent.trim(),
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: esError ? 4500 : 3000,
            timerProgressBar: true,
        });
    }

    // ---- Modal: nuevo / editar producto ----
    const overlay = document.getElementById('iv-modal-overlay');
    const form = document.getElementById('iv-form');

    if (overlay && form) {
        const titulo = document.getElementById('iv-modal-titulo');
        const campoId = document.getElementById('iv-f-id');
        const campoNombre = document.getElementById('iv-f-nombre');
        const campoCategoria = document.getElementById('iv-f-categoria');
        const campoCodigo = document.getElementById('iv-f-codigo');
        const campoPrecio = document.getElementById('iv-f-precio');
        const campoStock = document.getElementById('iv-f-stock');
        const campoStockMin = document.getElementById('iv-f-stock-min');
        const stockWrap = document.getElementById('iv-f-stock-wrap');
        const ayudaStock = document.getElementById('iv-f-stock-ayuda');

        function abrirModal(modo, datos) {
            form.reset();
            if (modo === 'editar') {
                titulo.textContent = 'Editar producto';
                form.action = 'index.php?accion=producto_editar';
                campoId.value = datos.id;
                campoNombre.value = datos.nombre;
                campoCategoria.value = datos.idCategoria;
                campoCodigo.value = datos.codigo || '';
                campoPrecio.value = datos.precio;
                campoStockMin.value = datos.stockMinimo;
                campoStock.disabled = true;
                campoStock.required = false;
                stockWrap.style.display = 'none';
                ayudaStock.hidden = false;
            } else {
                titulo.textContent = 'Nuevo producto';
                form.action = 'index.php?accion=producto_nuevo';
                campoId.value = '';
                campoStock.disabled = false;
                campoStock.required = true;
                stockWrap.style.display = '';
                ayudaStock.hidden = true;
            }
            overlay.hidden = false;
            campoNombre.focus();
        }

        function cerrarModal() { overlay.hidden = true; }

        const btnNuevo = document.getElementById('iv-btn-nuevo');
        if (btnNuevo) btnNuevo.addEventListener('click', () => abrirModal('crear', {}));

        document.querySelectorAll('[data-editar]').forEach((boton) => {
            boton.addEventListener('click', () => {
                abrirModal('editar', {
                    id: boton.dataset.id,
                    nombre: boton.dataset.nombre,
                    idCategoria: boton.dataset.idCategoria,
                    codigo: boton.dataset.codigo,
                    precio: boton.dataset.precio,
                    stockMinimo: boton.dataset.stockMinimo,
                });
            });
        });

        const btnCerrar = document.getElementById('iv-modal-cerrar');
        const btnCancelar = document.getElementById('iv-modal-cancelar');
        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
        if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);
        overlay.addEventListener('click', (evento) => { if (evento.target === overlay) cerrarModal(); });

        if (overlay.dataset.reabrirModo) {
            abrirModal(overlay.dataset.reabrirModo, {
                id: overlay.dataset.reabrirId || '',
                nombre: overlay.dataset.reabrirNombre || '',
                idCategoria: overlay.dataset.reabrirIdCategoria || '',
                codigo: overlay.dataset.reabrirCodigo || '',
                precio: overlay.dataset.reabrirPrecio || '',
                stockMinimo: overlay.dataset.reabrirStockMinimo || '',
            });
            if (overlay.dataset.reabrirModo === 'crear' && overlay.dataset.reabrirStock) {
                campoStock.value = overlay.dataset.reabrirStock;
            }
        }
    }

    // ---- Modal: ajustar stock ----
    const stockOverlay = document.getElementById('iv-stock-overlay');
    if (stockOverlay) {
        const campoStockId = document.getElementById('iv-stock-id');
        const campoStockCantidad = document.getElementById('iv-stock-cantidad');
        const nombreSpan = document.getElementById('iv-stock-nombre');
        const actualSpan = document.getElementById('iv-stock-actual-valor');

        function abrirStock(datos) {
            document.getElementById('iv-form-stock').reset();
            campoStockId.value = datos.id;
            nombreSpan.textContent = datos.nombre;
            actualSpan.textContent = datos.stockActual;
            stockOverlay.hidden = false;
            campoStockCantidad.focus();
        }
        function cerrarStock() { stockOverlay.hidden = true; }

        document.querySelectorAll('[data-stock]').forEach((boton) => {
            boton.addEventListener('click', () => {
                abrirStock({ id: boton.dataset.id, nombre: boton.dataset.nombre, stockActual: boton.dataset.stockActual });
            });
        });

        const btnCerrarStock = document.getElementById('iv-stock-cerrar');
        const btnCancelarStock = document.getElementById('iv-stock-cancelar');
        if (btnCerrarStock) btnCerrarStock.addEventListener('click', cerrarStock);
        if (btnCancelarStock) btnCancelarStock.addEventListener('click', cerrarStock);
        stockOverlay.addEventListener('click', (evento) => { if (evento.target === stockOverlay) cerrarStock(); });
    }

    document.addEventListener('keydown', (evento) => {
        if (evento.key !== 'Escape') return;
        if (overlay && !overlay.hidden) overlay.hidden = true;
        if (stockOverlay && !stockOverlay.hidden) stockOverlay.hidden = true;
    });

    // ---- Confirmacion antes de eliminar ----
    document.querySelectorAll('.iv-form-inline').forEach((formularioFila) => {
        formularioFila.addEventListener('submit', (evento) => {
            if (formularioFila.dataset.confirmado === '1') return;
            evento.preventDefault();
            const nombre = formularioFila.dataset.nombre;

            if (typeof Swal === 'undefined') {
                formularioFila.dataset.confirmado = '1';
                formularioFila.submit();
                return;
            }

            Swal.fire(Object.assign({}, temaSwal, {
                icon: 'warning',
                title: 'Eliminar producto',
                html: `¿Eliminar <strong>${nombre}</strong> del catalogo? Esta accion no se puede deshacer.`,
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
            })).then((resultado) => {
                if (resultado.isConfirmed) {
                    formularioFila.dataset.confirmado = '1';
                    formularioFila.submit();
                }
            });
        });
    });

    // ---- Filtro de productos: buscador + categoria ----
    const buscador = document.getElementById('iv-buscar');
    const filasProductos = Array.from(document.querySelectorAll('#iv-tbody tr'));
    const vacioProductos = document.getElementById('iv-vacio');
    const chipsCategoria = Array.from(document.querySelectorAll('#iv-filtro-categoria .iv-chip'));
    let categoriaActiva = 'todas';

    function aplicarFiltrosProductos() {
        const texto = (buscador ? buscador.value : '').trim().toLowerCase();
        let visibles = 0;
        filasProductos.forEach((fila) => {
            const coincideCategoria = categoriaActiva === 'todas' || fila.dataset.categoria === categoriaActiva;
            const coincideTexto = !texto || fila.dataset.nombre.includes(texto) || fila.dataset.codigo.includes(texto);
            const visible = coincideCategoria && coincideTexto;
            fila.hidden = !visible;
            if (visible) visibles++;
        });
        if (vacioProductos) vacioProductos.hidden = visibles !== 0;
    }

    if (buscador) buscador.addEventListener('input', aplicarFiltrosProductos);
    chipsCategoria.forEach((chip) => {
        chip.addEventListener('click', () => {
            chipsCategoria.forEach((c) => c.classList.remove('iv-chip--activo'));
            chip.classList.add('iv-chip--activo');
            categoriaActiva = chip.dataset.categoria;
            aplicarFiltrosProductos();
        });
    });

    // ---- Filtro del kardex ----
    const chipsMov = Array.from(document.querySelectorAll('#iv-filtro-mov .iv-chip'));
    chipsMov.forEach((chip) => {
        chip.addEventListener('click', () => {
            chipsMov.forEach((c) => c.classList.remove('iv-chip--activo'));
            chip.classList.add('iv-chip--activo');
            const tipo = chip.dataset.tipo;
            document.querySelectorAll('#iv-tabla-kardex tbody tr[data-tipo-mov]').forEach((fila) => {
                fila.hidden = tipo !== 'todos' && fila.dataset.tipoMov !== tipo;
            });
        });
    });
})();
