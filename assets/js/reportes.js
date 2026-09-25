/**
 * reportes.js
 * -----------------------------------------------------------------------
 * Vista de solo lectura salvo por las exportaciones (enlaces reales a
 * index.php?accion=reporte_pdf / reporte_csv, que responden con el
 * archivo). Aqui solo se muestra un toast breve de "generando..." al
 * hacer clic (mPDF puede tardar un segundo) y el aviso del flash con
 * SweetAlert2, igual que el resto de vistas migradas.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#212f39',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
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
    if (btnCsv) btnCsv.addEventListener('click', () => avisarGenerando('CSV'));
})();
