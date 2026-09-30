<?php
require_once __DIR__ . '/../../controller/RespaldoController.php';

$tituloPagina    = 'Respaldos';
$subtituloPagina = 'Copias de seguridad de la base de datos';
$vistaActiva     = 'respaldos';
require __DIR__ . '/../layout/header.php';

$respaldos = RespaldoController::listar();

$rutaTarea = 'C:\\wamp64\\bin\\php\\php8.0.30\\php.exe';
$rutaScript = 'C:\\wamp64\\www\\Gasolinera-Cerron-Grande-\\cli\\respaldo_automatico.php';
?>
<link rel="stylesheet" href="assets/css/respaldos.css">

<div class="rb-page">
    <span class="rb-page__esquina rb-page__esquina--tl"></span>
    <span class="rb-page__esquina rb-page__esquina--tr"></span>
    <span class="rb-page__esquina rb-page__esquina--bl"></span>
    <span class="rb-page__esquina rb-page__esquina--br"></span>

    <div class="rb-cabecera">
        <div>
            <div class="rb-eyebrow">Mantenimiento</div>
            <h2 class="rb-titulo">Respaldos de la base de datos</h2>
        </div>
        <form method="post" action="index.php?accion=respaldo_crear" id="form-crear-respaldo">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
            <button class="rb-btn rb-btn--primario" type="submit">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                Crear respaldo ahora
            </button>
        </form>
    </div>

    <div class="rb-nota">
        Un respaldo es una copia completa de la base de datos en un momento dado. Sirve para volver atras si algo sale mal (un error, una prueba, un cambio equivocado) &mdash; nunca para el uso diario del sistema.
    </div>

    <div class="rb-bloque">
        <div class="rb-bloque__titulo">Respaldos guardados</div>
        <div class="rb-tabla-wrap">
            <table class="rb-tabla">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th>Tamano</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($respaldos)): ?>
                    <tr><td colspan="4" class="rb-vacio">Todavia no hay ningun respaldo guardado.</td></tr>
                    <?php else: foreach ($respaldos as $r): ?>
                    <tr>
                        <td class="rb-archivo"><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                        <td><?= htmlspecialchars($r['tamano_legible']) ?></td>
                        <td class="rb-acciones">
                            <a class="rb-btn-mini" href="index.php?accion=respaldo_descargar&nombre=<?= urlencode($r['nombre']) ?>" title="Descargar">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                            </a>
                            <form method="post" action="index.php?accion=respaldo_restaurar" class="rb-form-restaurar" data-nombre="<?= htmlspecialchars($r['nombre']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
                                <input type="hidden" name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>">
                                <button class="rb-btn-mini rb-btn-mini--alerta" type="submit" title="Restaurar este respaldo">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
                                </button>
                            </form>
                            <form method="post" action="index.php?accion=respaldo_eliminar" class="rb-form-eliminar" data-nombre="<?= htmlspecialchars($r['nombre']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
                                <input type="hidden" name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>">
                                <button class="rb-btn-mini rb-btn-mini--borrar" type="submit" title="Eliminar este respaldo">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rb-bloque">
        <div class="rb-bloque__titulo">Restaurar desde un archivo .sql</div>
        <p class="rb-texto-mini">Sube un respaldo que tengas guardado fuera del sistema (por ejemplo, de otra computadora) y restauralo directamente. Antes de aplicarlo, el sistema guarda automaticamente un respaldo del estado actual.</p>
        <form method="post" action="index.php?accion=respaldo_subir_restaurar" enctype="multipart/form-data" id="form-subir-restaurar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Sesion::tokenCsrf()) ?>">
            <div class="rb-subir">
                <input type="file" name="archivo" id="archivo-respaldo" accept=".sql" required>
                <button class="rb-btn rb-btn--alerta" type="submit">Restaurar desde este archivo</button>
            </div>
        </form>
    </div>

    <div class="rb-bloque">
        <div class="rb-bloque__titulo">Respaldo automatico diario (opcional)</div>
        <p class="rb-texto-mini">
            Esto hace que el sistema se respalde solo, todos los dias, sin que nadie tenga que entrar aqui y presionar el boton. Es opcional: mientras no lo actives siguiendo estos pasos, todo sigue funcionando normal y siempre puedes crear un respaldo manual con el boton de arriba.
        </p>

        <ol class="rb-pasos">
            <li>En Windows, haz clic en el boton de Inicio (la banderita, abajo a la izquierda) y escribe <strong>Programador de tareas</strong>. Abre el programa que aparezca con ese nombre (tambien se llama <em>Task Scheduler</em>).</li>
            <li>En el panel de la derecha, haz clic en <strong>"Crear tarea basica..."</strong>.</li>
            <li>Ponle un nombre, por ejemplo <strong>Respaldo Gasolinera</strong>, y haz clic en <strong>Siguiente</strong>.</li>
            <li>Elige <strong>Diario</strong> y haz clic en <strong>Siguiente</strong>. Escoge la hora en que quieres que se haga (por ejemplo, 2:00 a.m., cuando nadie esta usando el sistema) y haz clic en <strong>Siguiente</strong> otra vez.</li>
            <li>Elige la opcion <strong>Iniciar un programa</strong> y haz clic en <strong>Siguiente</strong>.</li>
            <li>
                Vas a ver un campo que dice <strong>"Programa o script"</strong>. Copia exactamente esto y pegalo ahi
                (el boton "Copiar" lo copia solo, para que no haya que escribirlo a mano):
                <div class="rb-codigo rb-codigo--copiable">
                    <code id="rb-ruta-programa"><?= htmlspecialchars($rutaTarea) ?></code>
                    <button type="button" class="rb-btn-copiar" data-copiar="rb-ruta-programa">Copiar</button>
                </div>
            </li>
            <li>
                Mas abajo hay otro campo que dice <strong>"Agregar argumentos (opcional)"</strong>. Copia y pega esto ahi:
                <div class="rb-codigo rb-codigo--copiable">
                    <code id="rb-ruta-script">"<?= htmlspecialchars($rutaScript) ?>"</code>
                    <button type="button" class="rb-btn-copiar" data-copiar="rb-ruta-script">Copiar</button>
                </div>
            </li>
            <li>Haz clic en <strong>Siguiente</strong> y despues en <strong>Finalizar</strong>. Listo: desde ese momento el sistema se va a respaldar solo, todos los dias, a la hora que elegiste. No hace falta hacer nada mas.</li>
        </ol>

        <p class="rb-texto-mini rb-texto-mini--nota">El script tambien borra por su cuenta los respaldos con mas de 30 dias, para que no se vaya llenando el disco con el tiempo.</p>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/respaldos.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
