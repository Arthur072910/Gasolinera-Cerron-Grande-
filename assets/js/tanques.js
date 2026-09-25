/**
 * tanques.js
 * -----------------------------------------------------------------------
 * Comportamiento de Administrador > Tanques (Arduino):
 *   - Modal para registrar una lectura manual (mismo patron que
 *     usuarios.js): abrir/cerrar con boton, click fuera, tecla Escape.
 *   - Aviso del resultado de la ultima accion con SweetAlert2
 *     (assets/js/vendor/sweetalert2.min.js), reemplazando el banner de
 *     texto plano de esta vista.
 * No cambia el formulario en si: sigue siendo un <form method="post">
 * normal hacia index.php?accion=tanque_lectura.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#212f39',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
        cancelButtonColor: '#3a4753',
    };

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

    const overlay = document.getElementById('tq-modal-overlay');
    const btnNuevo = document.getElementById('tq-btn-nuevo');
    const btnCerrar = document.getElementById('tq-modal-cerrar');
    const btnCancelar = document.getElementById('tq-modal-cancelar');
    if (!overlay || !btnNuevo) return;

    function abrirModal() {
        overlay.hidden = false;
        const primerCampo = overlay.querySelector('select, input');
        if (primerCampo) primerCampo.focus();
    }

    function cerrarModal() {
        overlay.hidden = true;
    }

    btnNuevo.addEventListener('click', abrirModal);
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);
    overlay.addEventListener('click', (evento) => {
        if (evento.target === overlay) cerrarModal();
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !overlay.hidden) cerrarModal();
    });
})();
