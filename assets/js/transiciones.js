/**
 * transiciones.js
 * -----------------------------------------------------------------------
 * Efecto compartido en TODA la app (se carga desde view/layout/footer.php):
 * al hacer clic en un enlace interno (rail de navegacion, "volver a..."
 * dentro de alguna vista, etc.), el contenido principal (.main) se
 * desvanece brevemente antes de navegar; al cargar la siguiente pagina,
 * .main entra con un fundido (animacion definida en assets/css/style.css).
 * El resultado es una transicion suave entre vistas aunque cada clic
 * siga siendo una navegacion normal de pagina completa.
 *
 * No intercepta: enlaces externos, con target distinto de _self, con
 * atributo download, clics con boton derecho o con tecla modificadora
 * (para no romper "abrir en pestana nueva"), ni formularios (los POST
 * de accion ya manejan su propio flujo con mensajes flash).
 * -----------------------------------------------------------------------
 */
(function () {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const main = document.querySelector('.main');
    if (!main) return;

    const DURACION_MS = 160;

    function esNavegacionInterna(enlace) {
        if (!enlace || !enlace.href) return false;
        if (enlace.target && enlace.target !== '_self') return false;
        if (enlace.hasAttribute('download')) return false;
        let url;
        try {
            url = new URL(enlace.href, window.location.href);
        } catch (error) {
            return false;
        }
        return url.origin === window.location.origin;
    }

    document.addEventListener('click', (evento) => {
        if (evento.defaultPrevented || evento.button !== 0) return;
        if (evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) return;

        const enlace = evento.target.closest('a[href]');
        if (!enlace || !esNavegacionInterna(enlace)) return;
        if (enlace.href === window.location.href) return;

        evento.preventDefault();
        main.classList.add('main--saliendo');
        setTimeout(() => { window.location.href = enlace.href; }, DURACION_MS);
    });
})();
