<?php
require_once __DIR__ . '/../../controller/AsistenciaController.php';

$tituloPagina    = 'Asistencia';
$subtituloPagina = 'Registro automatico de entrada y salida por PIN';
$vistaActiva     = 'asistencia';
require __DIR__ . '/../layout/header.php';

$registros = AsistenciaController::registros();
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Como se genera este registro</span></div>
        <p style="margin:0;">
            Cada vez que un Cajero o Despachador inicia sesion con su PIN, el sistema
            guarda automaticamente la marca de tiempo en la tabla <code>asistencia</code>.
            No requiere que nadie lo anote a mano.
        </p>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Bitacora de entradas y salidas</span></div>

        <div class="filtro-tabla">
            <label for="filtro-rol">Filtrar por rol:</label>
            <select id="filtro-rol">
                <option value="todos">Todos</option>
                <option value="Administrador">Administrador</option>
                <option value="Cajero">Cajero</option>
                <option value="Despachador">Despachador</option>
            </select>
        </div>

        <table class="bitacora" id="tabla-asistencia">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Entrada</th><th>Salida</th></tr></thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
                <tr data-rol="<?= htmlspecialchars($r['rol']) ?>">
                    <td><?= htmlspecialchars($r['usuario']) ?></td>
                    <td><?= htmlspecialchars($r['rol']) ?></td>
                    <td><?= htmlspecialchars($r['entrada']) ?></td>
                    <td><?= $r['salida'] === '--' ? '<span class="estado estado--advertencia"><span class="estado__marca"></span> En turno</span>' : htmlspecialchars($r['salida']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('filtro-rol').addEventListener('change', function () {
    const valor = this.value;
    document.querySelectorAll('#tabla-asistencia tbody tr').forEach((fila) => {
        fila.style.display = (valor === 'todos' || fila.dataset.rol === valor) ? '' : 'none';
    });
});
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
