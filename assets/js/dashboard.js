/**
 * dashboard.js
 * -----------------------------------------------------------------------
 * Panel general: vista de solo lectura, sin formularios propios. Lo
 * unico que hace este archivo es reemplazar el banner de aviso de
 * texto plano por un toast de SweetAlert2 (por si se llega aqui con un
 * mensaje flash pendiente, p.ej. tras una redireccion), igual que en el
 * resto de vistas ya migradas.
 * -----------------------------------------------------------------------
 */
(function () {
    const banner = document.querySelector('.banner-aviso');
    if (!banner || !banner.classList.contains('mostrar') || typeof Swal === 'undefined') return;

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
})();
