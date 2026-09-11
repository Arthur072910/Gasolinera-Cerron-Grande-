<?php
require_once __DIR__ . '/../../controller/TiendaController.php';
require_once __DIR__ . '/../../controller/InventarioController.php';

$tituloPagina    = 'Inventario y tienda';
$subtituloPagina = 'Kardex, categorias y alertas de stock minimo';
$vistaActiva     = 'inventario';
require __DIR__ . '/../layout/header.php';

$categorias  = TiendaController::categorias();
$productos   = TiendaController::productos();
$alertas     = InventarioController::alertasStock();
$movimientos = InventarioController::movimientosKardex();

$nombresCategoria = [];
foreach ($categorias as $c) { $nombresCategoria[$c['id']] = $c['nombre']; }

$idsAlerta = array_column($alertas, 'producto');
?>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Catalogo de productos</span>
            <button class="btn" type="button" id="btn-mostrar-form-producto">+ Nuevo producto</button>
        </div>

        <div class="bloque-paso" id="form-producto" data-oculto="1">
            <div class="bloque-paso__titulo">Registrar producto nuevo</div>
            <form method="post" action="index.php?accion=producto_nuevo">
                <div class="form-grid">
                    <div class="campo">
                        <label for="p_nombre">Nombre del producto</label>
                        <input type="text" name="nombre" id="p_nombre" required>
                    </div>
                    <div class="campo">
                        <label for="p_categoria">Categoria</label>
                        <select name="id_categoria" id="p_categoria">
                            <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="p_codigo">Codigo de barras</label>
                        <input type="text" name="codigo_barras" id="p_codigo">
                    </div>
                    <div class="campo">
                        <label for="p_precio">Precio de venta</label>
                        <input type="text" name="precio" id="p_precio" inputmode="decimal" placeholder="0.00" required>
                    </div>
                    <div class="campo">
                        <label for="p_stock">Stock inicial</label>
                        <input type="text" name="stock" id="p_stock" inputmode="numeric" placeholder="0" required>
                    </div>
                    <div class="campo">
                        <label for="p_stock_min">Stock minimo (alerta)</label>
                        <input type="text" name="stock_minimo" id="p_stock_min" inputmode="numeric" placeholder="0" required>
                    </div>
                </div>
                <button type="submit" class="btn btn--lleno">Guardar producto</button>
            </form>
        </div>

        <table class="bitacora">
            <thead><tr><th>Producto</th><th>Categoria</th><th>Codigo</th><th>Precio</th><th>Stock</th></tr></thead>
            <tbody>
            <?php foreach ($productos as $p): $enAlerta = in_array($p['nombre'], $idsAlerta, true); ?>
                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($nombresCategoria[$p['id_categoria']] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['codigo_barras']) ?></td>
                    <td>$<?= number_format($p['precio'], 2) ?></td>
                    <td>
                        <?= $p['stock'] ?>
                        <?php if ($enAlerta): ?><span class="badge badge--alerta">bajo</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Alertas de stock minimo</span></div>
        <table class="bitacora">
            <thead><tr><th>Producto</th><th>Stock actual</th><th>Stock minimo</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($alertas as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['producto']) ?></td>
                    <td><?= $a['stock_actual'] ?></td>
                    <td><?= $a['stock_minimo'] ?></td>
                    <td><span class="estado estado--alerta"><span class="estado__marca"></span> Reabastecer</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo"><span>Kardex &mdash; movimientos recientes</span></div>
        <div class="filtro-tabla">
            <label for="filtro-tipo-mov">Filtrar por tipo:</label>
            <select id="filtro-tipo-mov">
                <option value="todos">Todos</option>
                <option value="entrada">Entradas</option>
                <option value="salida">Salidas</option>
            </select>
        </div>
        <table class="bitacora" id="tabla-kardex">
            <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th></tr></thead>
            <tbody>
            <?php foreach ($movimientos as $m): ?>
                <tr data-tipo-mov="<?= $m['tipo'] ?>">
                    <td><?= htmlspecialchars($m['fecha']) ?></td>
                    <td><?= htmlspecialchars($m['producto']) ?></td>
                    <td><?= $m['tipo'] === 'entrada' ? 'Entrada' : 'Salida' ?></td>
                    <td><?= $m['cantidad'] ?></td>
                    <td><?= htmlspecialchars($m['motivo']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ticket">
    <div class="ticket__borde-perforado"></div>
    <div class="ticket__cuerpo">
        <div class="ticket__titulo">
            <span>Categorias</span>
        </div>
        <div class="chips">
            <?php foreach ($categorias as $c): ?>
            <span class="chip" style="cursor:default;"><?= htmlspecialchars($c['nombre']) ?></span>
            <?php endforeach; ?>
        </div>
        <form method="post" action="index.php?accion=categoria_nueva" style="margin-top:16px; display:flex; gap:8px; max-width:420px;">
            <input type="text" name="nombre" placeholder="Nombre de la nueva categoria" required style="flex:1; font-family:var(--f-mono); padding:10px; border:2px solid var(--c-line);">
            <button type="submit" class="btn">Agregar</button>
        </form>
    </div>
</div>

<script src="assets/js/formularios.js"></script>
<script>
document.getElementById('filtro-tipo-mov').addEventListener('change', function () {
    const valor = this.value;
    document.querySelectorAll('#tabla-kardex tbody tr').forEach((fila) => {
        fila.style.display = (valor === 'todos' || fila.dataset.tipoMov === valor) ? '' : 'none';
    });
});
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
