/**
 * bitacora.js
 * -----------------------------------------------------------------------
 * Vista de solo lectura (filtros por GET, sin formularios que muten
 * datos). Solo se encarga del toast de SweetAlert2 para el flash
 * pendiente, igual que el resto de vistas migradas.
 * -----------------------------------------------------------------------
 */
(function () {
    try {
        const banner = document.querySelector('.banner-aviso');
        if (banner && banner.classList.contains('mostrar') && typeof Swal !== 'undefined') {
            const esError = banner.classList.contains('banner-aviso--error');
            Swal.fire({
                background: '#212f39',
                color: '#eef1f2',
                confirmButtonColor: '#eb5a28',
                icon: esError ? 'error' : 'success',
                title: banner.textContent.trim(),
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: esError ? 4500 : 3000,
                timerProgressBar: true,
            });
        }
    } catch (error) {
        console.error('[bitacora.js] No se pudo mostrar el aviso:', error);
    }
})();
