/**
 * login.js
 * -----------------------------------------------------------------------
 * Comportamiento visual de la pantalla de acceso:
 *   - Sincroniza las "cajas" del PIN con el input real (que es el que
 *     de verdad se envia en el POST a index.php?accion=login).
 *   - El teclado numerico en pantalla (pensado para tablet, sin teclado
 *     fisico) escribe sobre ese mismo input real.
 *   - Filtra la entrada a solo digitos.
 *   - Si el servidor devolvio un error, sacude el campo una vez.
 *   - Reloj en vivo de la barra de estado.
 *   - Breve secuencia de "verificando" (~1.3s) antes de enviar el
 *     formulario de verdad: es solo percepcion de espera, el POST real
 *     sigue siendo el mismo <form> normal (funciona igual sin JS).
 * -----------------------------------------------------------------------
 */
(function () {
    const reloj = document.getElementById('login-reloj');
    if (reloj) {
        const actualizarReloj = () => {
            reloj.textContent = new Date().toLocaleTimeString('es-SV', { hour12: false });
        };
        actualizarReloj();
        setInterval(actualizarReloj, 1000);
    }

    const input = document.getElementById('pin');
    const contenedor = document.querySelector('.login-pin');
    const teclado = document.querySelector('.login-teclado');
    if (!input || !contenedor) return;

    const cajas = Array.from(contenedor.querySelectorAll('.login-pin__caja'));
    const maxLargo = input.maxLength || 6;

    function repintar() {
        const valor = input.value;
        cajas.forEach((caja, indice) => {
            caja.dataset.lleno = indice < valor.length ? '1' : '0';
            caja.dataset.activo = indice === valor.length ? '1' : '0';
        });
    }

    function dispararInput() {
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, maxLargo);
        repintar();
    });

    input.addEventListener('focus', repintar);
    input.addEventListener('blur', () => {
        cajas.forEach((caja) => { caja.dataset.activo = '0'; });
    });

    contenedor.addEventListener('click', () => input.focus());

    if (teclado) {
        teclado.addEventListener('click', (evento) => {
            const tecla = evento.target.closest('[data-tecla]');
            if (!tecla) return;
            const valor = tecla.dataset.tecla;

            if (valor === 'borrar') {
                input.value = input.value.slice(0, -1);
            } else if (valor === 'limpiar') {
                input.value = '';
            } else if (input.value.length < maxLargo) {
                input.value += valor;
            }

            dispararInput();
            input.focus();
        });
    }

    if (contenedor.dataset.conError === '1') {
        contenedor.classList.add('esta-mal');
        input.value = '';
        setTimeout(() => contenedor.classList.remove('esta-mal'), 350);
    }

    repintar();
    input.focus();

    // ---- Secuencia de "verificando" antes de enviar ----
    const formulario = document.getElementById('form-login');
    const boton = document.getElementById('btn-login');
    const textoBoton = boton ? boton.querySelector('.login-btn__texto') : null;
    const progreso = document.getElementById('login-progreso');
    const progresoRelleno = document.getElementById('login-progreso-relleno');
    const estado = document.getElementById('login-estado');

    if (formulario && boton) {
        formulario.addEventListener('submit', (evento) => {
            if (boton.classList.contains('cargando')) return; // evita doble envio
            evento.preventDefault();

            if (!input.value) { input.focus(); return; }

            boton.classList.add('cargando');
            boton.disabled = true;
            if (textoBoton) textoBoton.textContent = 'Verificando';
            contenedor.dataset.bloqueado = '1';
            if (teclado) teclado.dataset.bloqueado = '1';

            if (progreso) progreso.dataset.activo = '1';
            if (estado) estado.dataset.activo = '1';

            requestAnimationFrame(() => {
                if (progresoRelleno) progresoRelleno.style.width = '100%';
            });

            const mensajes = ['Verificando credenciales...', 'Autenticando en el servidor...', 'Accediendo al sistema...'];
            let paso = 0;
            if (estado) estado.textContent = mensajes[paso];
            const intervalo = setInterval(() => {
                paso += 1;
                if (estado && mensajes[paso]) estado.textContent = mensajes[paso];
            }, 480);

            setTimeout(() => {
                clearInterval(intervalo);
                formulario.submit();
            }, 1450);
        });
    }
})();
