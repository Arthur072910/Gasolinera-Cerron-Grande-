/**
 * dashboard.js
 * -----------------------------------------------------------------------
 * Panel general: vista de solo lectura, sin formularios propios.
 *   - Toast de SweetAlert2 para el flash pendiente, igual que el resto
 *     de vistas migradas.
 *   - Dona de ventas de hoy por origen (pista vs tienda) con Chart.js.
 * -----------------------------------------------------------------------
 */
(function () {
    // Aislado en su propio try/catch: si el toast fallara por lo que
    // sea, no debe impedir que la grafica de abajo se dibuje.
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
        console.error('[dashboard.js] No se pudo mostrar el aviso:', error);
    }

    try {
        if (typeof Chart === 'undefined' || !window.DATOS_DASHBOARD) return;
        const lienzo = document.getElementById('pg-grafica-origen');
        if (!lienzo) return;
        const paleta = window.PALETA_GRAFICA || {};
        const datos = window.DATOS_DASHBOARD;

        new Chart(lienzo, {
            type: 'doughnut',
            data: {
                labels: ['Pista (combustible)', 'Tienda'],
                datasets: [{
                    data: [datos.totalPista, datos.totalTienda],
                    backgroundColor: [paleta.naranja, paleta.pizarra],
                    borderColor: paleta.panel,
                    borderWidth: 3,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `$${ctx.parsed.toFixed(2)}` } },
                },
            },
        });
    } catch (error) {
        console.error('[dashboard.js] No se pudo dibujar la grafica:', error);
    }
})();
