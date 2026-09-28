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
        console.error('[precios.js] No se pudo mostrar el aviso:', error);
    }

    // ---- Grafica de tendencia de precios (Chart.js) ----
    try {
        if (typeof Chart !== 'undefined' && window.DATOS_PRECIOS && window.DATOS_PRECIOS.length) {
            const lienzo = document.getElementById('pr-grafica-tendencia');
            if (lienzo) {
                const paleta = window.PALETA_GRAFICA || {};
                const colorPorCombustible = { Super: paleta.naranja, Regular: paleta.pizarra, Diesel: paleta.ceniza };
                const porCombustible = {};
                window.DATOS_PRECIOS.forEach((h) => {
                    if (!porCombustible[h.combustible]) porCombustible[h.combustible] = [];
                    porCombustible[h.combustible].push({ x: h.desde, y: h.precio });
                });

                new Chart(lienzo, {
                    type: 'line',
                    data: {
                        datasets: Object.keys(porCombustible).map((nombre) => ({
                            label: nombre,
                            data: porCombustible[nombre],
                            borderColor: colorPorCombustible[nombre] || paleta.ceniza,
                            backgroundColor: (colorPorCombustible[nombre] || paleta.ceniza) + '22',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            tension: 0,
                            stepped: 'before',
                            fill: false,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'nearest', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 10, boxHeight: 10 } },
                            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: $${ctx.parsed.y.toFixed(3)}` } },
                        },
                        scales: {
                            x: { type: 'category', grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
                            y: { grid: { color: paleta.rejilla }, ticks: { callback: (v) => `$${v}` } },
                        },
                    },
                });
            }
        }
    } catch (error) {
        console.error('[precios.js] No se pudo dibujar la grafica:', error);
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
