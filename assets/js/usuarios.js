/**
 * usuarios.js
 * -----------------------------------------------------------------------
 * Comportamiento de la vista Administrador > Usuarios:
 *   - Un solo modal reutilizado para "Nuevo usuario" y "Editar usuario";
 *     en modo editar cambia el action del formulario, precarga los
 *     datos desde los atributos data-* del boton "Editar" de la fila,
 *     y los campos de PIN quedan opcionales (se conserva si se dejan
 *     en blanco; el backend hace la misma validacion por si JS esta
 *     desactivado, en cuyo caso el modal simplemente no se abre y el
 *     formulario de creacion normal del servidor seguiria funcionando
 *     via progressive enhancement no aplica aqui, por eso el boton
 *     "+ Nuevo usuario" solo tiene sentido con JS activo).
 *   - Filtro de busqueda por nombre/rol en la tabla.
 *   - Confirmacion antes de desactivar/activar un usuario y aviso del
 *     resultado, ambos con SweetAlert2 (assets/js/vendor/sweetalert2.min.js)
 *     tematizado con la misma paleta oscura/naranja del resto de la vista.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#1a232c',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
        cancelButtonColor: '#3a4753',
    };

    function avisar(opciones) {
        if (typeof Swal === 'undefined') { return; }
        Swal.fire(Object.assign({}, temaSwal, opciones));
    }

    // ---- Notificacion del resultado de la ultima accion (flash del servidor) ----
    const banner = document.querySelector('.banner-aviso');
    if (banner && banner.classList.contains('mostrar') && typeof Swal !== 'undefined') {
        const esError = banner.classList.contains('banner-aviso--error');
        Swal.fire(Object.assign({}, temaSwal, {
            icon: esError ? 'error' : 'success',
            title: banner.textContent.trim(),
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: esError ? 4500 : 3000,
            timerProgressBar: true,
        }));
    }

    const overlay = document.getElementById('usr-modal-overlay');
    const form = document.getElementById('usr-form');
    if (!overlay || !form) return;

    const titulo = document.getElementById('usr-modal-titulo');
    const campoId = document.getElementById('usr-f-id');
    const campoNombre = document.getElementById('usr-f-nombre');
    const campoRol = document.getElementById('usr-f-rol');
    const campoPin = document.getElementById('usr-f-pin');
    const campoPin2 = document.getElementById('usr-f-pin2');
    const etiquetaPin = document.getElementById('usr-f-pin-label');
    const ayudaPin = document.getElementById('usr-f-pin-ayuda');

    function abrirModal(modo, datos) {
        form.reset();
        if (modo === 'editar') {
            titulo.textContent = 'Editar usuario';
            form.action = 'index.php?accion=usuario_editar';
            campoId.value = datos.id;
            campoNombre.value = datos.nombre;
            campoRol.value = datos.idRol;
            campoPin.required = false;
            campoPin2.required = false;
            etiquetaPin.textContent = 'Nuevo PIN (opcional)';
            ayudaPin.hidden = false;
        } else {
            titulo.textContent = 'Nuevo usuario';
            form.action = 'index.php?accion=usuario_nuevo';
            campoId.value = '';
            campoPin.required = true;
            campoPin2.required = true;
            etiquetaPin.textContent = 'PIN de acceso';
            ayudaPin.hidden = true;
        }
        overlay.hidden = false;
        campoNombre.focus();
    }

    function cerrarModal() {
        overlay.hidden = true;
    }

    const btnNuevo = document.getElementById('usr-btn-nuevo');
    if (btnNuevo) btnNuevo.addEventListener('click', () => abrirModal('crear', {}));

    document.querySelectorAll('[data-editar]').forEach((boton) => {
        boton.addEventListener('click', () => {
            abrirModal('editar', {
                id: boton.dataset.id,
                nombre: boton.dataset.nombre,
                idRol: boton.dataset.idRol,
            });
        });
    });

    const btnCerrar = document.getElementById('usr-modal-cerrar');
    const btnCancelar = document.getElementById('usr-modal-cancelar');
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);
    overlay.addEventListener('click', (evento) => {
        if (evento.target === overlay) cerrarModal();
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !overlay.hidden) cerrarModal();
    });

    form.addEventListener('submit', (evento) => {
        if (campoPin.value !== campoPin2.value) {
            evento.preventDefault();
            avisar({ icon: 'warning', title: 'PIN no coincide', text: 'El PIN y su confirmacion no coinciden.' });
            return;
        }
        if (campoPin.value && !/^\d{4,6}$/.test(campoPin.value)) {
            evento.preventDefault();
            avisar({ icon: 'warning', title: 'PIN invalido', text: 'El PIN debe tener entre 4 y 6 digitos numericos.' });
        }
    });

    // Si el servidor devolvio un error de este formulario, reabrir el
    // modal en el modo correspondiente para que el usuario no pierda
    // lo que estaba haciendo.
    if (overlay.dataset.reabrirModo) {
        abrirModal(overlay.dataset.reabrirModo, {
            id: overlay.dataset.reabrirId || '',
            nombre: overlay.dataset.reabrirNombre || '',
            idRol: overlay.dataset.reabrirIdRol || '',
        });
    }

    // ---- Confirmacion antes de activar/desactivar ----
    document.querySelectorAll('.usr-form-inline').forEach((formularioFila) => {
        formularioFila.addEventListener('submit', (evento) => {
            if (formularioFila.dataset.confirmado === '1') { return; } // ya confirmado, dejar pasar

            evento.preventDefault();
            const accion = formularioFila.dataset.nuevaAccion === 'activar' ? 'Activar' : 'Desactivar';
            const nombre = formularioFila.dataset.nombre;

            if (typeof Swal === 'undefined') {
                formularioFila.dataset.confirmado = '1';
                formularioFila.submit();
                return;
            }

            Swal.fire(Object.assign({}, temaSwal, {
                icon: 'warning',
                title: `${accion} usuario`,
                html: accion === 'Desactivar'
                    ? `<strong>${nombre}</strong> no podra iniciar sesion hasta que se reactive. &iquest;Continuar?`
                    : `<strong>${nombre}</strong> podra volver a iniciar sesion con su PIN. &iquest;Continuar?`,
                showCancelButton: true,
                confirmButtonText: accion,
                cancelButtonText: 'Cancelar',
            })).then((resultado) => {
                if (resultado.isConfirmed) {
                    formularioFila.dataset.confirmado = '1';
                    formularioFila.submit();
                }
            });
        });
    });

    // ---- Filtro de busqueda ----
    const buscador = document.getElementById('usr-buscar');
    const filas = Array.from(document.querySelectorAll('#usr-tbody tr'));
    const vacio = document.getElementById('usr-vacio');

    if (buscador) {
        buscador.addEventListener('input', () => {
            const texto = buscador.value.trim().toLowerCase();
            let visibles = 0;
            filas.forEach((fila) => {
                const coincide = !texto
                    || fila.dataset.nombre.includes(texto)
                    || fila.dataset.rol.includes(texto);
                fila.hidden = !coincide;
                if (coincide) visibles++;
            });
            if (vacio) vacio.hidden = visibles !== 0;
        });
    }
})();
