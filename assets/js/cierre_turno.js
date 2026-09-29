/**
 * cierre_turno.js
 * -----------------------------------------------------------------------
 * Vista de conciliacion de turno del Despachador: calculo en vivo de la
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
        console.error('[cierre_turno.js] No se pudo mostrar el aviso:', error);
    }

    try {
        const efectivoEsperado = (window.DATOS_CIERRE_TURNO && window.DATOS_CIERRE_TURNO.efectivoEsperado) || 0;
        const input = document.getElementById('monto_entregado');
        const salida = document.getElementById('diferencia-turno');
        if (!input || !salida) return;

        function actualizar() {
            salida.classList.remove('ct-diferencia--exacto', 'ct-diferencia--sobra', 'ct-diferencia--falta', 'ct-diferencia--pendiente');

            if (input.value.trim() === '') {
                salida.textContent = 'Pendiente de contar';
                salida.classList.add('ct-diferencia--pendiente');
                return;
            }

            const declarado = parseFloat(input.value) || 0;
            const diferencia = declarado - efectivoEsperado;
            const signo = diferencia > 0 ? '+' : '';
            salida.textContent = `${signo}$${diferencia.toFixed(2)}`;
            if (Math.abs(diferencia) < 0.005) salida.classList.add('ct-diferencia--exacto');
            else if (diferencia > 0) salida.classList.add('ct-diferencia--sobra');
            else salida.classList.add('ct-diferencia--falta');
        }
        input.addEventListener('input', actualizar);
        actualizar();

        const formulario = document.getElementById('form-cierre-turno');
        if (formulario) {
            formulario.addEventListener('submit', (evento) => {
                const declarado = parseFloat(input.value);
                if (isNaN(declarado) || declarado < 0) {
                    evento.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(Object.assign({}, temaSwal, {
                            icon: 'warning',
                            title: 'Ingresa el efectivo contado antes de confirmar.',
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
        console.error('[cierre_turno.js] No se pudo calcular la diferencia:', error);
    }
})();
