/**
 * rail.js
 * -----------------------------------------------------------------------
 * Unico efecto: un resaltado que se desliza detras del item del menu
 * activo y, al pasar el mouse, sigue al item que se esta explorando
 * (volviendo al activo cuando el mouse sale de la lista). Se carga en
 * todas las vistas (view/layout/footer.php), asi que revisa que los
 * elementos existan antes de usarlos.
 * -----------------------------------------------------------------------
 */
(function () {
    const lista = document.getElementById('rail-lista');
    const resaltado = document.getElementById('rail-resaltado');
    if (!lista || !resaltado) return;

    const items = Array.from(lista.querySelectorAll('.rail__key'));
    if (items.length === 0) return;

    function moverA(elemento, animar) {
        if (!elemento) {
            resaltado.classList.remove('rail__resaltado--visible');
            return;
        }
        if (!animar) resaltado.style.transition = 'none';
        resaltado.style.transform = `translateY(${elemento.offsetTop}px)`;
        resaltado.style.height = `${elemento.offsetHeight}px`;
        resaltado.classList.add('rail__resaltado--visible');
        if (!animar) {
            // Fuerza el reflow para que la siguiente animacion si tenga transicion.
            resaltado.offsetHeight;
            resaltado.style.transition = '';
        }
    }

    const activo = lista.querySelector('.rail__key.activo');
    moverA(activo, false);

    items.forEach((item) => {
        item.addEventListener('mouseenter', () => moverA(item, true));
    });

    lista.addEventListener('mouseleave', () => moverA(lista.querySelector('.rail__key.activo'), true));

    window.addEventListener('resize', () => moverA(lista.querySelector('.rail__key.activo'), false));
})();

/**
 * Manija para ocultar/mostrar el sidebar (solo vistas de POS, ver
 * $railColapsable en view/layout/header.php). El estado se recuerda en
 * localStorage porque estas pantallas se recargan por completo despues
 * de cada venta/despacho, y no tendria sentido que el rail reaparezca
 * cada vez que el cajero/despachador acaba de ocultarlo.
 */
(function () {
    const manija = document.getElementById('rail-manija');
    const shell = document.querySelector('.app-shell');
    if (!manija || !shell) return;

    manija.addEventListener('click', () => {
        const oculto = shell.classList.toggle('app-shell--rail-oculto');
        try {
            localStorage.setItem('pos_rail_oculto', oculto ? '1' : '0');
        } catch (error) { /* localStorage no disponible: el estado no se recuerda, no es grave */ }
    });
})();
