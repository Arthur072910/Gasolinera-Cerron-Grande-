/**
 * precios.js
 * -----------------------------------------------------------------------
 * Comportamiento de Administrador > Precios: modal para registrar un
 * precio nuevo (mismo patron que usuarios.js/tanques.js) y aviso del
 * resultado con SweetAlert2 en vez del banner de texto plano.
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

    const overlay = document.getElementById('pr-modal-overlay');
    const btnNuevo = document.getElementById('pr-btn-nuevo');
    const btnCerrar = document.getElementById('pr-modal-cerrar');
    const btnCancelar = document.getElementById('pr-modal-cancelar');
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
