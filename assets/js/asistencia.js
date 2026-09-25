/**
 * asistencia.js
 * -----------------------------------------------------------------------
 * Vista de solo lectura: filtro combinado por rol (chips) y busqueda por
 * nombre sobre la bitacora ya cargada en la tabla, mas el aviso del
 * flash con SweetAlert2 (igual que el resto de vistas migradas).
 * -----------------------------------------------------------------------
 */
(function () {
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

    const filas = Array.from(document.querySelectorAll('#as-tbody tr'));
    const vacio = document.getElementById('as-vacio');
    const buscador = document.getElementById('as-buscar');
    const chips = Array.from(document.querySelectorAll('#as-filtro-rol .as-chip'));
    if (filas.length === 0 && chips.length === 0) return;

    let rolActivo = 'todos';

    function aplicarFiltros() {
        const texto = (buscador ? buscador.value : '').trim().toLowerCase();
        let visibles = 0;

        filas.forEach((fila) => {
            const coincideRol = rolActivo === 'todos' || fila.dataset.rol === rolActivo;
            const coincideTexto = !texto || fila.dataset.nombre.includes(texto);
            const visible = coincideRol && coincideTexto;
            fila.hidden = !visible;
            if (visible) visibles++;
        });

        if (vacio && filas.length > 0) vacio.hidden = visibles !== 0;
    }

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            chips.forEach((c) => c.classList.remove('as-chip--activo'));
            chip.classList.add('as-chip--activo');
            rolActivo = chip.dataset.rol;
            aplicarFiltros();
        });
    });

    if (buscador) buscador.addEventListener('input', aplicarFiltros);
})();
