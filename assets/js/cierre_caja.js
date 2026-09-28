/**
 * cierre_caja.js
 * -----------------------------------------------------------------------
 * Vista de conciliacion de caja del Cajero: calculo en vivo de la
 * diferencia contra el efectivo esperado (fondo inicial + ventas en
 * efectivo, ver TurnoController::obtenerResumenCierre) y aviso del
 * resultado con SweetAlert2.
 * -----------------------------------------------------------------------
 */
(function () {
    const temaSwal = {
        background: '#212f39',
        color: '#eef1f2',
        confirmButtonColor: '#eb5a28',
        cancelButtonColor: '#3a4753',
    };

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
        console.error('[cierre_caja.js] No se pudo mostrar el aviso:', error);
    }

    try {
        const efectivoEsperado = (window.DATOS_CIERRE_CAJA && window.DATOS_CIERRE_CAJA.efectivoEsperado) || 0;
        const input = document.getElementById('monto_entregado');
        const salida = document.getElementById('diferencia-caja');
        if (!input || !salida) return;

        function actualizar() {
            salida.classList.remove('cc-diferencia--exacto', 'cc-diferencia--sobra', 'cc-diferencia--falta', 'cc-diferencia--pendiente');

            // Antes de que el cajero escriba algo no hay nada que
            // comparar todavia: mostrar "pendiente" en vez de calcular
            // contra $0.00 (se veia como una alerta de faltante falsa).
            if (input.value.trim() === '') {
                salida.textContent = 'Pendiente de contar';
                salida.classList.add('cc-diferencia--pendiente');
                return;
            }

            const declarado = parseFloat(input.value) || 0;
            const diferencia = declarado - efectivoEsperado;
            const signo = diferencia > 0 ? '+' : '';
            salida.textContent = `${signo}$${diferencia.toFixed(2)}`;
            if (Math.abs(diferencia) < 0.005) salida.classList.add('cc-diferencia--exacto');
            else if (diferencia > 0) salida.classList.add('cc-diferencia--sobra');
            else salida.classList.add('cc-diferencia--falta');
        }
        input.addEventListener('input', actualizar);
        actualizar();

        const formulario = document.getElementById('form-cierre-caja');
        if (formulario) {
            formulario.addEventListener('submit', (evento) => {
                const declarado = parseFloat(input.value);
                if (isNaN(declarado) || declarado < 0) {
                    evento.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(Object.assign({}, temaSwal, {
                            icon: 'warning',
                            title: 'Ingresa el monto de efectivo contado antes de confirmar.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3500,
                        }));
                    }
                }
            });
        }
    } catch (error) {
        console.error('[cierre_caja.js] No se pudo calcular la diferencia:', error);
    }
})();
