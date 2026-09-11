/**
 * formularios.js
 * -----------------------------------------------------------------------
 * Comportamiento generico y reutilizable para las pantallas del
 * Administrador que tienen un formulario "Nuevo/Registrar" oculto por
 * defecto (usuarios, productos, categorias, proveedores/cisternas, precios).
 *
 * Convencion usada en las vistas: un boton con id="btn-mostrar-form-<algo>"
 * abre/cierra el bloque con id="form-<algo>" (clase .bloque-paso y el
 * atributo data-oculto="1"/"0" definido en el CSS). Los formularios en
 * si son <form method="post"> normales que envian a index.php?accion=...;
 * la confirmacion o el error se muestran con el banner que rellena
 * view/layout/header.php a partir del mensaje flash de la sesion.
 * -----------------------------------------------------------------------
 */
(function () {
    document.querySelectorAll('[id^="btn-mostrar-form-"]').forEach((btn) => {
        const idBloque = btn.id.replace('btn-mostrar-form-', 'form-');
        const bloque = document.getElementById(idBloque);
        if (!bloque) return;
        btn.addEventListener('click', () => {
            bloque.dataset.oculto = bloque.dataset.oculto === '0' ? '1' : '0';
        });
    });
})();
