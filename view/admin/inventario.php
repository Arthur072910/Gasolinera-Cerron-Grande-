<?php
require_once __DIR__ . '/../../controller/InventarioController.php';

$tituloPagina    = 'Inventario y tienda';
$subtituloPagina = 'Catalogo, kardex y alertas de stock minimo';
$vistaActiva     = 'inventario';
require __DIR__ . '/../layout/header.php';

$categorias  = InventarioController::categorias();
$productos   = InventarioController::productos();
$movimientos = InventarioController::movimientosKardex();

$nombresCategoria = [];
foreach ($categorias as $c) { $nombresCategoria[$c['id']] = $c['nombre']; }

$totalBajoStock = 0;
foreach ($productos as $p) {
    if ($p['stock'] <= $p['stock_minimo']) { $totalBajoStock++; }
}

// Si la ultima accion de este formulario termino en error, recuperamos
// lo que el usuario habia escrito para reabrir el modal sin perderlo.
$reabrir = ($flash && $flash['tipo'] === 'error') ? Sesion::leerFlashDatos() : null;
?>
<link rel="stylesheet" href="assets/css/inventario.css">

<div class="iv-page">
    <span class="iv-page__esquina iv-page__esquina--tl"></span>
    <span class="iv-page__esquina iv-page__esquina--tr"></span>
    <span class="iv-page__esquina iv-page__esquina--bl"></span>
    <span class="iv-page__esquina iv-page__esquina--br"></span>

    <div class="iv-cabecera">
        <div>
            <div class="iv-eyebrow">Tienda de conveniencia</div>
            <h2 class="iv-titulo">Catalogo de productos</h2>
        </div>
        <button class="iv-btn iv-btn--primario" type="button" id="iv-btn-nuevo">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo producto
        </button>
    </div>

    <div class="iv-stats">
        <div class="iv-stat">
            <div class="iv-stat__valor"><?= count($productos) ?></div>
            <div class="iv-stat__etiqueta">Productos totales</div>
        </div>
        <div class="iv-stat iv-stat--alerta">
            <div class="iv-stat__valor"><?= $totalBajoStock ?></div>
            <div class="iv-stat__etiqueta">Bajo stock minimo</div>
        </div>
        <div class="iv-stat">
            <div class="iv-stat__valor"><?= count($categorias) ?></div>
            <div class="iv-stat__etiqueta">Categorias</div>
        </div>
    </div>

    <div class="iv-toolbar">
        <div class="iv-buscador-wrap">
            <svg class="iv-toolbar__icono" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" class="iv-buscador" id="iv-buscar" placeholder="Buscar por nombre o codigo...">
        </div>
        <div class="iv-chips" id="iv-filtro-categoria">
            <button class="iv-chip iv-chip--activo" type="button" data-categoria="todas">Todas</button>
            <?php foreach ($categorias as $c): ?>
            <button class="iv-chip" type="button" data-categoria="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="iv-tabla-wrap">
        <table class="iv-tabla">
            <thead><tr><th>Producto</th><th>Categoria</th><th>Codigo</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr></thead>
            <tbody id="iv-tbody">
            <?php foreach ($productos as $p): $bajoStock = $p['stock'] <= $p['stock_minimo']; ?>
                <tr data-nombre="<?= htmlspecialchars(mb_strtolower($p['nombre'])) ?>"
                    data-codigo="<?= htmlspecialchars(mb_strtolower((string) $p['codigo_barras'])) ?>"
                    data-categoria="<?= $p['id_categoria'] ?>">
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><span class="iv-catchip"><?= htmlspecialchars($nombresCategoria[$p['id_categoria']] ?? '—') ?></span></td>
                    <td><?= $p['codigo_barras'] ? htmlspecialchars($p['codigo_barras']) : '<span class="iv-sin-codigo">—</span>' ?></td>
                    <td>$<?= number_format($p['precio'], 2) ?></td>
                    <td>
                        <span class="iv-stock <?= $bajoStock ? 'iv-stock--bajo' : '' ?>"><?= (int) $p['stock'] ?></span>
                        <?php if ($bajoStock): ?><span class="iv-badge-bajo">bajo minimo</span><?php endif; ?>
                    </td>
                    <td class="iv-acciones">
                        <button class="iv-icon-btn" type="button" title="Ajustar stock"
                                data-stock
                                data-id="<?= $p['id'] ?>"
                                data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                data-stock-actual="<?= (int) $p['stock'] ?>">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <button class="iv-icon-btn" type="button" title="Editar producto"
                                data-editar
                                data-id="<?= $p['id'] ?>"
                                data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                data-id-categoria="<?= $p['id_categoria'] ?>"
                                data-codigo="<?= htmlspecialchars((string) $p['codigo_barras'], ENT_QUOTES) ?>"
                                data-precio="<?= $p['precio'] ?>"
                                data-stock-minimo="<?= (int) $p['stock_minimo'] ?>">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                        <form method="post" action="index.php?accion=producto_eliminar" class="iv-form-inline"
                              data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">
                            <input type="hidden" name="id_producto" value="<?= $p['id'] ?>">
                            <button class="iv-icon-btn iv-icon-btn--peligro" type="submit" title="Eliminar producto">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="iv-vacio" id="iv-vacio" hidden>No se encontraron productos con ese criterio.</p>
    </div>

    <div class="iv-bloque">
        <div class="iv-bloque__cabecera">
            <div class="iv-bloque__titulo">Kardex &middot; movimientos recientes</div>
            <div class="iv-chips" id="iv-filtro-mov">
                <button class="iv-chip iv-chip--activo" type="button" data-tipo="todos">Todos</button>
                <button class="iv-chip" type="button" data-tipo="entrada">Entradas</button>
                <button class="iv-chip" type="button" data-tipo="salida">Salidas</button>
            </div>
        </div>
        <div class="iv-tabla-wrap">
            <table class="iv-tabla" id="iv-tabla-kardex">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th></tr></thead>
                <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr><td colspan="5" class="iv-vacio-fila">Todavia no hay movimientos registrados.</td></tr>
                <?php endif; ?>
                <?php foreach ($movimientos as $m): ?>
                    <tr data-tipo-mov="<?= $m['tipo'] ?>">
                        <td><?= htmlspecialchars($m['fecha']) ?></td>
                        <td><?= htmlspecialchars($m['producto']) ?></td>
                        <td><span class="iv-tipo-mov iv-tipo-mov--<?= $m['tipo'] ?>"><?= $m['tipo'] === 'entrada' ? 'Entrada' : 'Salida' ?></span></td>
                        <td><?= $m['cantidad'] ?></td>
                        <td><?= htmlspecialchars($m['motivo']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="iv-bloque">
        <div class="iv-bloque__titulo">Categorias</div>
        <div class="iv-chips" style="margin-bottom:14px;">
            <?php foreach ($categorias as $c): ?>
            <span class="iv-catchip"><?= htmlspecialchars($c['nombre']) ?></span>
            <?php endforeach; ?>
        </div>
        <form method="post" action="index.php?accion=categoria_nueva" class="iv-form-categoria">
            <input type="text" name="nombre" placeholder="Nombre de la nueva categoria" required>
            <button type="submit" class="iv-btn">Agregar</button>
        </form>
    </div>
</div>

<!-- Modal: crear/editar producto -->
<div class="iv-modal-overlay" id="iv-modal-overlay" hidden
     <?php if ($reabrir && $reabrir['modo'] !== 'stock'): ?>
     data-reabrir-modo="<?= htmlspecialchars($reabrir['modo']) ?>"
     data-reabrir-id="<?= htmlspecialchars($reabrir['id'] ?? '') ?>"
     data-reabrir-nombre="<?= htmlspecialchars($reabrir['nombre'] ?? '', ENT_QUOTES) ?>"
     data-reabrir-id-categoria="<?= htmlspecialchars($reabrir['id_categoria'] ?? '') ?>"
     data-reabrir-codigo="<?= htmlspecialchars($reabrir['codigo_barras'] ?? '', ENT_QUOTES) ?>"
     data-reabrir-precio="<?= htmlspecialchars($reabrir['precio'] ?? '') ?>"
     data-reabrir-stock="<?= htmlspecialchars($reabrir['stock'] ?? '') ?>"
     data-reabrir-stock-minimo="<?= htmlspecialchars($reabrir['stock_minimo'] ?? '') ?>"
     <?php endif; ?>>
    <div class="iv-modal" role="dialog" aria-modal="true" aria-labelledby="iv-modal-titulo">
        <div class="iv-modal__barra"></div>
        <button class="iv-modal__cerrar" type="button" id="iv-modal-cerrar" aria-label="Cerrar">&times;</button>

        <div class="iv-modal__eyebrow">Inventario &middot; catalogo</div>
        <div class="iv-modal__titulo" id="iv-modal-titulo">Nuevo producto</div>

        <?php if ($reabrir && $reabrir['modo'] !== 'stock'): ?>
        <div class="iv-error"><span>&#9888;</span><span><?= htmlspecialchars($flash['mensaje']) ?></span></div>
        <?php endif; ?>

        <form id="iv-form" method="post" action="index.php?accion=producto_nuevo">
            <input type="hidden" name="id_producto" id="iv-f-id">

            <div class="iv-campo">
                <label for="iv-f-nombre">Nombre del producto</label>
                <input type="text" name="nombre" id="iv-f-nombre" required>
            </div>

            <div class="iv-campo-grupo">
                <div class="iv-campo">
                    <label for="iv-f-categoria">Categoria</label>
                    <select name="id_categoria" id="iv-f-categoria">
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="iv-campo">
                    <label for="iv-f-codigo">Codigo de barras</label>
                    <input type="text" name="codigo_barras" id="iv-f-codigo">
                </div>
            </div>

            <div class="iv-campo-grupo">
                <div class="iv-campo">
                    <label for="iv-f-precio">Precio de venta</label>
                    <input type="text" name="precio" id="iv-f-precio" inputmode="decimal" placeholder="0.00" required>
                </div>
                <div class="iv-campo" id="iv-f-stock-wrap">
                    <label for="iv-f-stock">Stock inicial</label>
                    <input type="text" name="stock" id="iv-f-stock" inputmode="numeric" placeholder="0">
                </div>
            </div>

            <div class="iv-campo">
                <label for="iv-f-stock-min">Stock minimo (alerta)</label>
                <input type="text" name="stock_minimo" id="iv-f-stock-min" inputmode="numeric" placeholder="0" required>
            </div>
            <p class="iv-ayuda" id="iv-f-stock-ayuda" hidden>
                El stock actual se cambia desde el boton "Ajustar stock" de la tabla, no aqui.
            </p>

            <div class="iv-modal__acciones">
                <button type="button" class="iv-btn" id="iv-modal-cancelar">Cancelar</button>
                <button type="submit" class="iv-btn iv-btn--primario">Guardar producto</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: ajustar stock -->
<div class="iv-modal-overlay" id="iv-stock-overlay" hidden>
    <div class="iv-modal iv-modal--chico" role="dialog" aria-modal="true" aria-labelledby="iv-stock-titulo">
        <div class="iv-modal__barra"></div>
        <button class="iv-modal__cerrar" type="button" id="iv-stock-cerrar" aria-label="Cerrar">&times;</button>

        <div class="iv-modal__eyebrow">Inventario &middot; ajuste manual</div>
        <div class="iv-modal__titulo" id="iv-stock-titulo">Ajustar stock</div>
        <p class="iv-stock-actual">Producto: <strong id="iv-stock-nombre">&mdash;</strong> &middot; stock actual: <strong id="iv-stock-actual-valor">0</strong></p>

        <form id="iv-form-stock" method="post" action="index.php?accion=producto_stock">
            <input type="hidden" name="id_producto" id="iv-stock-id">
            <div class="iv-campo">
                <label for="iv-stock-cantidad">Cantidad a agregar (usa negativo para restar)</label>
                <input type="text" name="cantidad" id="iv-stock-cantidad" inputmode="numeric" placeholder="0" required>
            </div>
            <div class="iv-modal__acciones">
                <button type="button" class="iv-btn" id="iv-stock-cancelar">Cancelar</button>
                <button type="submit" class="iv-btn iv-btn--primario">Confirmar ajuste</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/vendor/sweetalert2.min.js"></script>
<script src="assets/js/inventario.js"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
