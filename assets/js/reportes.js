/**
 * reportes.js
 * -----------------------------------------------------------------------
 * Vista de solo lectura salvo por las exportaciones (enlaces reales a
 * index.php?accion=reporte_pdf / reporte_excel, que responden con el
 * archivo). Aqui solo se muestra un toast breve de "generando..." al
 * hacer clic (mPDF/PhpSpreadsheet pueden tardar un segundo) y el aviso del flash con
 * SweetAlert2, igual que el resto de vistas migradas.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#212f39',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
    };

    // Aislado en su propio try/catch: si el toast fallara por lo que
    // sea, no debe impedir que las graficas de abajo se dibujen.
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

        function avisarGenerando(formato) {
            if (typeof Swal === 'undefined') return;
            Swal.fire(Object.assign({}, temaSwal, {
                icon: 'info',
                title: `Generando ${formato}...`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1800,
            }));
        }

        const btnPdf = document.getElementById('rp-btn-pdf');
        const btnCsv = document.getElementById('rp-btn-csv');
        if (btnPdf) btnPdf.addEventListener('click', () => avisarGenerando('PDF'));
        if (btnCsv) btnCsv.addEventListener('click', () => avisarGenerando('Excel'));
    } catch (error) {
        console.error('[reportes.js] No se pudo mostrar el aviso:', error);
    }

    // ---- Graficas (Chart.js) ----
    try {
        if (typeof Chart === 'undefined' || !window.DATOS_REPORTE) return;
        const paleta = window.PALETA_GRAFICA || {};
        const datos = window.DATOS_REPORTE;

        const colorPorCombustible = { Super: paleta.naranja, Regular: paleta.pizarra, Diesel: paleta.ceniza };

        const lienzoCombustible = document.getElementById('rp-grafica-combustible');
        if (lienzoCombustible && datos.ventasCombustible.length) {
            new Chart(lienzoCombustible, {
                type: 'bar',
                data: {
                    labels: datos.ventasCombustible.map((v) => v.combustible),
                    datasets: [{
                        label: 'Galones despachados',
                        data: datos.ventasCombustible.map((v) => v.galones),
                        backgroundColor: datos.ventasCombustible.map((v) => colorPorCombustible[v.combustible] || paleta.pizarra),
                        borderRadius: 4,
                        maxBarThickness: 46,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y.toFixed(1)} gal` } },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: paleta.rejilla }, beginAtZero: true },
                    },
                },
            });
        }

        const lienzoProductos = document.getElementById('rp-grafica-productos');
        if (lienzoProductos && datos.topProductos.length) {
            new Chart(lienzoProductos, {
                type: 'bar',
                data: {
                    labels: datos.topProductos.map((p) => p.producto),
                    datasets: [{
                        label: 'Unidades vendidas',
                        data: datos.topProductos.map((p) => p.unidades),
                        backgroundColor: paleta.naranja,
                        borderRadius: 4,
                        maxBarThickness: 26,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.x} unidades` } },
                    },
                    scales: {
                        x: { grid: { color: paleta.rejilla }, beginAtZero: true, ticks: { precision: 0 } },
                        y: { grid: { display: false } },
                    },
                },
            });
        }
    } catch (error) {
        console.error('[reportes.js] No se pudo dibujar las graficas:', error);
    }
})();
