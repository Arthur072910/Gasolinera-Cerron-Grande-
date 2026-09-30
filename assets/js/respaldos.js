/**
 * respaldos.js
 * -----------------------------------------------------------------------
 * Restaurar un respaldo reemplaza TODA la base de datos actual por la
 * del archivo — es la accion mas destructiva de todo el sistema, asi
 * que aqui se pide una confirmacion explicita (escribir "RESTAURAR")
 * antes de dejar pasar el envio real del formulario. Eliminar un
 * respaldo es mas liviano (solo borra un archivo, no datos de negocio)
 * pero igual pide confirmacion simple.
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
                timer: esError ? 6000 : 3500,
                timerProgressBar: true,
            }));
        }
    } catch (error) {
        console.error('[respaldos.js] No se pudo mostrar el aviso:', error);
    }

    function avisarProcesando(titulo) {
        if (typeof Swal === 'undefined') return;
        Swal.fire(Object.assign({}, temaSwal, {
            icon: 'info',
            title: titulo,
            text: 'Esto puede tardar unos segundos...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        }));
    }

    // ---- Crear respaldo ----
    const formCrear = document.getElementById('form-crear-respaldo');
    if (formCrear) {
        formCrear.addEventListener('submit', () => avisarProcesando('Generando respaldo...'));
    }

    // ---- Restaurar (desde la lista): pide escribir RESTAURAR ----
    document.querySelectorAll('.rb-form-restaurar').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            evento.preventDefault();
            const nombre = form.dataset.nombre;
            confirmarRestauracion(`Se reemplazara TODA la base de datos actual con "${nombre}". Esta accion no se puede deshacer desde aqui (aunque se guarda un respaldo de seguridad antes).`)
                .then((ok) => {
                    if (ok) {
                        avisarProcesando('Restaurando base de datos...');
                        form.submit();
                    }
                });
        });
    });

    // ---- Restaurar desde archivo subido: misma confirmacion ----
    const formSubir = document.getElementById('form-subir-restaurar');
    if (formSubir) {
        formSubir.addEventListener('submit', (evento) => {
            evento.preventDefault();
            const input = document.getElementById('archivo-respaldo');
            if (!input.files || input.files.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(Object.assign({}, temaSwal, { icon: 'warning', title: 'Selecciona un archivo .sql primero.' }));
                }
                return;
            }
            confirmarRestauracion(`Se reemplazara TODA la base de datos actual con el archivo "${input.files[0].name}". Esta accion no se puede deshacer desde aqui (aunque se guarda un respaldo de seguridad antes).`)
                .then((ok) => {
                    if (ok) {
                        avisarProcesando('Subiendo y restaurando...');
                        formSubir.submit();
                    }
                });
        });
    }

    function confirmarRestauracion(mensaje) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(window.confirm(mensaje + '\n\nEscribe RESTAURAR para confirmar (no disponible sin SweetAlert).'));
        }
        return Swal.fire(Object.assign({}, temaSwal, {
            icon: 'warning',
            title: 'Restaurar base de datos',
            html: `<p style="text-align:left">${mensaje}</p><p style="text-align:left">Escribe <strong>RESTAURAR</strong> para confirmar:</p>`,
            input: 'text',
            inputPlaceholder: 'RESTAURAR',
            showCancelButton: true,
            confirmButtonText: 'Restaurar',
            cancelButtonText: 'Cancelar',
            preConfirm: (valor) => {
                if (valor !== 'RESTAURAR') {
                    Swal.showValidationMessage('Escribe exactamente RESTAURAR para confirmar.');
                    return false;
                }
                return true;
            },
        })).then((resultado) => resultado.isConfirmed);
    }

    // ---- Copiar al portapapeles (guia de tarea programada) ----
    document.querySelectorAll('.rb-btn-copiar').forEach((boton) => {
        boton.addEventListener('click', () => {
            const elemento = document.getElementById(boton.dataset.copiar);
            if (!elemento) return;
            const texto = elemento.textContent;

            const marcarCopiado = () => {
                const textoOriginal = boton.textContent;
                boton.textContent = 'Copiado';
                boton.classList.add('rb-btn-copiar--hecho');
                setTimeout(() => {
                    boton.textContent = textoOriginal;
                    boton.classList.remove('rb-btn-copiar--hecho');
                }, 1800);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(marcarCopiado).catch(() => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(Object.assign({}, temaSwal, { icon: 'warning', title: 'No se pudo copiar. Selecciona el texto manualmente.' }));
                    }
                });
            } else {
                // Respaldo para navegadores viejos sin API de portapapeles.
                const areaTemporal = document.createElement('textarea');
                areaTemporal.value = texto;
                areaTemporal.style.position = 'fixed';
                areaTemporal.style.opacity = '0';
                document.body.appendChild(areaTemporal);
                areaTemporal.select();
                try {
                    document.execCommand('copy');
                    marcarCopiado();
                } catch (error) {
                    console.error('[respaldos.js] No se pudo copiar:', error);
                }
                document.body.removeChild(areaTemporal);
            }
        });
    });

    // ---- Eliminar un respaldo: confirmacion simple ----
    document.querySelectorAll('.rb-form-eliminar').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            evento.preventDefault();
            const nombre = form.dataset.nombre;
            if (typeof Swal === 'undefined') {
                if (window.confirm(`Eliminar el respaldo "${nombre}"?`)) form.submit();
                return;
            }
            Swal.fire(Object.assign({}, temaSwal, {
                icon: 'question',
                title: 'Eliminar respaldo',
                text: `Se eliminara el archivo "${nombre}". Esto no afecta la base de datos actual.`,
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
            })).then((resultado) => {
                if (resultado.isConfirmed) form.submit();
            });
        });
    });
})();
