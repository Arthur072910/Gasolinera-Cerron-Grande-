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

    // Aislado en su propio try/catch: si el toast fallara por lo que
    // sea, no debe impedir que la grafica de abajo se dibuje.
    try {
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
    } catch (error) {
        console.error('[tanques.js] No se pudo mostrar el aviso:', error);
    }

    // ---- Grafica de tendencia de niveles (Chart.js) ----
    try {
        if (typeof Chart !== 'undefined' && window.DATOS_TANQUES && window.DATOS_TANQUES.length) {
            const lienzo = document.getElementById('tq-grafica-tendencia');
            if (lienzo) {
                const paleta = window.PALETA_GRAFICA || {};
                const coloresPorTanque = [paleta.naranja, paleta.pizarra, paleta.ceniza, paleta.verde];
                const porTanque = {};
                window.DATOS_TANQUES.forEach((h) => {
                    if (!porTanque[h.tanque]) porTanque[h.tanque] = [];
                    porTanque[h.tanque].push({ x: h.fecha, y: h.galones });
                });

                new Chart(lienzo, {
                    type: 'line',
                    data: {
                        datasets: Object.keys(porTanque).map((nombre, indice) => ({
                            label: nombre,
                            data: porTanque[nombre],
                            borderColor: coloresPorTanque[indice % coloresPorTanque.length],
                            backgroundColor: coloresPorTanque[indice % coloresPorTanque.length] + '22',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            tension: 0.25,
                            fill: true,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'nearest', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 10, boxHeight: 10 } },
                            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString()} gal` } },
                        },
                        scales: {
                            x: { type: 'category', grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
                            y: { grid: { color: paleta.rejilla }, beginAtZero: true },
                        },
                    },
                });
            }
        }
    } catch (error) {
        console.error('[tanques.js] No se pudo dibujar la grafica:', error);
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
