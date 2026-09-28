/**
 * proveedores.js
 * -----------------------------------------------------------------------
 * Comportamiento de Administrador > Proveedores y cisternas:
 *   - Modal reutilizado para "Nuevo proveedor" / "Editar proveedor"
 *     (mismo patron que usuarios.js/inventario.js).
 *   - Modal separado para "Nueva recepcion de cisterna", con el calculo
 *     en vivo de la diferencia facturado/medido que ya existia.
 *   - Confirmacion con SweetAlert2 antes de eliminar un proveedor.
 *   - Buscador sobre la tabla de proveedores.
 *   - Aviso del resultado de la ultima accion con SweetAlert2.
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

    // ---- Modal: nuevo / editar proveedor ----
    const overlay = document.getElementById('pv-modal-overlay');
    const form = document.getElementById('pv-form');

    if (overlay && form) {
        const titulo = document.getElementById('pv-modal-titulo');
        const campoId = document.getElementById('pv-f-id');
        const campoNombre = document.getElementById('pv-f-nombre');
        const campoRegistro = document.getElementById('pv-f-registro');
        const campoTelefono = document.getElementById('pv-f-telefono');

        function abrirModal(modo, datos) {
            form.reset();
            if (modo === 'editar') {
                titulo.textContent = 'Editar proveedor';
                form.action = 'index.php?accion=proveedor_editar';
                campoId.value = datos.id;
                campoNombre.value = datos.nombre;
                campoRegistro.value = datos.registro;
                campoTelefono.value = datos.telefono || '';
            } else {
                titulo.textContent = 'Nuevo proveedor';
                form.action = 'index.php?accion=proveedor_nuevo';
                campoId.value = '';
            }
            overlay.hidden = false;
            campoNombre.focus();
        }
        function cerrarModal() { overlay.hidden = true; }

        const btnNuevo = document.getElementById('pv-btn-nuevo');
        if (btnNuevo) btnNuevo.addEventListener('click', () => abrirModal('crear', {}));

        document.querySelectorAll('[data-editar]').forEach((boton) => {
            boton.addEventListener('click', () => {
                abrirModal('editar', {
                    id: boton.dataset.id,
                    nombre: boton.dataset.nombre,
                    registro: boton.dataset.registro,
                    telefono: boton.dataset.telefono,
                });
            });
        });

        const btnCerrar = document.getElementById('pv-modal-cerrar');
        const btnCancelar = document.getElementById('pv-modal-cancelar');
        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
        if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);
        overlay.addEventListener('click', (evento) => { if (evento.target === overlay) cerrarModal(); });

        if (overlay.dataset.reabrirModo) {
            abrirModal(overlay.dataset.reabrirModo, {
                id: overlay.dataset.reabrirId || '',
                nombre: overlay.dataset.reabrirNombre || '',
                registro: overlay.dataset.reabrirRegistro || '',
                telefono: overlay.dataset.reabrirTelefono || '',
            });
        }
    }

    // ---- Modal: nueva recepcion ----
    const recepcionOverlay = document.getElementById('pv-recepcion-overlay');
    if (recepcionOverlay) {
        const btnRecepcion = document.getElementById('pv-btn-recepcion');
        const btnCerrarR = document.getElementById('pv-recepcion-cerrar');
        const btnCancelarR = document.getElementById('pv-recepcion-cancelar');

        function abrirRecepcion() { recepcionOverlay.hidden = false; }
        function cerrarRecepcion() { recepcionOverlay.hidden = true; }

        if (btnRecepcion) btnRecepcion.addEventListener('click', abrirRecepcion);
        if (btnCerrarR) btnCerrarR.addEventListener('click', cerrarRecepcion);
        if (btnCancelarR) btnCancelarR.addEventListener('click', cerrarRecepcion);
        recepcionOverlay.addEventListener('click', (evento) => { if (evento.target === recepcionOverlay) cerrarRecepcion(); });

        if (recepcionOverlay.dataset.reabrir === '1') {
            const asignar = (id, valor) => { const el = document.getElementById(id); if (el && valor) el.value = valor; };
            asignar('r_proveedor', recepcionOverlay.dataset.reabrirProveedor);
            asignar('r_tanque', recepcionOverlay.dataset.reabrirTanque);
            asignar('r_factura', recepcionOverlay.dataset.reabrirFactura);
            asignar('r_costo', recepcionOverlay.dataset.reabrirCosto);
            asignar('r_facturados', recepcionOverlay.dataset.reabrirFacturados);
            asignar('r_medidos', recepcionOverlay.dataset.reabrirMedidos);
            asignar('r_nivel_cm', recepcionOverlay.dataset.reabrirNivel);
            abrirRecepcion();
        }

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && !recepcionOverlay.hidden) cerrarRecepcion();
        });

        // ---- Calculo en vivo de la diferencia facturado/medido ----
        const facturados = document.getElementById('r_facturados');
        const medidos = document.getElementById('r_medidos');
        const salida = document.getElementById('r_diferencia');
        const aviso = document.getElementById('r_diferencia_aviso');

        function calcularDiferencia() {
            const f = parseFloat(facturados.value) || 0;
            const m = parseFloat(medidos.value) || 0;
            const diferencia = f - m;
            salida.textContent = `${diferencia.toFixed(1)} gal`;
            salida.style.color = Math.abs(diferencia) > 10 ? '#eb5a28' : '#3ddc84';
            aviso.textContent = Math.abs(diferencia) > 10
                ? 'Diferencia alta: revisar la entrega antes de confirmar.'
                : 'Diferencia dentro de un rango normal.';
        }
        if (facturados && medidos) {
            facturados.addEventListener('input', calcularDiferencia);
            medidos.addEventListener('input', calcularDiferencia);
        }
    }

    if (overlay) {
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && !overlay.hidden) overlay.hidden = true;
        });
    }

    // ---- Confirmacion antes de eliminar ----
    document.querySelectorAll('.pv-form-inline').forEach((formularioFila) => {
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
                title: 'Eliminar proveedor',
                html: `¿Eliminar <strong>${nombre}</strong>? Esta accion no se puede deshacer.`,
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

    // ---- Buscador de proveedores ----
    const buscador = document.getElementById('pv-buscar');
    const filas = Array.from(document.querySelectorAll('#pv-tbody tr'));
    const vacio = document.getElementById('pv-vacio');

    if (buscador) {
        buscador.addEventListener('input', () => {
            const texto = buscador.value.trim().toLowerCase();
            let visibles = 0;
            filas.forEach((fila) => {
                const coincide = !texto || fila.dataset.nombre.includes(texto);
                fila.hidden = !coincide;
                if (coincide) visibles++;
            });
            if (vacio) vacio.hidden = visibles !== 0;
        });
    }
})();
