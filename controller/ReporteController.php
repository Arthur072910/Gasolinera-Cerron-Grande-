<?php
/**
 * Controlador: Reportes gerenciales
 * Consultas agregadas sobre `ventas`, `detalle_ventas_combustible` y
 * `detalle_ventas_tienda`, todas acotadas al dia en curso. Tambien
 * genera la exportacion en PDF (mPDF, ver composer.json) y CSV/Excel
 * del mismo reporte.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Venta.php';
require_once __DIR__ . '/../model/DetalleVentaCombustible.php';
require_once __DIR__ . '/../model/DetalleVentaTienda.php';
require_once __DIR__ . '/../model/Turno.php';
require_once __DIR__ . '/../vendor/autoload.php';

class ReporteController
{
    public static function resumen(): array
    {
        $conexion = Database::obtenerConexion();
        return [
            'ventas_hoy'          => Venta::totalHoy($conexion),
            'galones_despachados' => DetalleVentaCombustible::totalGalonesHoy($conexion),
            'productos_vendidos'  => DetalleVentaTienda::totalUnidadesHoy($conexion),
            'turnos_cerrados'     => Turno::contarCerradosHoy($conexion),
        ];
    }

    public static function ventasPorCombustible(): array
    {
        return DetalleVentaCombustible::resumenPorCombustibleHoy(Database::obtenerConexion());
    }

    public static function topProductos(): array
    {
        $filas = DetalleVentaTienda::obtenerTopProductosHoy(Database::obtenerConexion(), 5);
        return array_map(fn ($f) => [
            'producto' => $f['producto'],
            'unidades' => (int) $f['unidades'],
            'total'    => (float) $f['total'],
        ], $filas);
    }

    /** Genera el PDF del reporte del dia y lo envia directo al navegador. */
    public static function generarPdf(): void
    {
        $resumen           = self::resumen();
        $ventasCombustible = self::ventasPorCombustible();
        $topProductos      = self::topProductos();
        $fecha             = date('d/m/Y H:i');

        $filasCombustible = '';
        foreach ($ventasCombustible as $v) {
            $filasCombustible .= '<tr>'
                . '<td>' . htmlspecialchars($v['combustible']) . '</td>'
                . '<td class="num">' . number_format($v['galones'], 1) . ' gal</td>'
                . '<td class="num">$' . number_format($v['total'], 2) . '</td>'
                . '</tr>';
        }
        if ($ventasCombustible === []) {
            $filasCombustible = '<tr><td colspan="3" class="vacio">Sin ventas de combustible registradas hoy.</td></tr>';
        }

        $filasProductos = '';
        foreach ($topProductos as $p) {
            $filasProductos .= '<tr>'
                . '<td>' . htmlspecialchars($p['producto']) . '</td>'
                . '<td class="num">' . $p['unidades'] . '</td>'
                . '<td class="num">$' . number_format($p['total'], 2) . '</td>'
                . '</tr>';
        }
        if ($topProductos === []) {
            $filasProductos = '<tr><td colspan="3" class="vacio">Sin ventas de tienda registradas hoy.</td></tr>';
        }

        $html = '
        <style>
            body { font-family: sans-serif; color: #17222b; }
            .marca { color: #eb5a28; font-weight: bold; font-size: 9pt; letter-spacing: 1px; text-transform: uppercase; }
            h1 { font-size: 16pt; margin: 2px 0 0; }
            .subt { color: #567384; font-size: 9pt; margin-bottom: 14px; }
            .stats { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            .stats td { width: 25%; border: 0.5pt solid #cccccc; padding: 8px; text-align: center; }
            .stats .valor { font-size: 13pt; font-weight: bold; color: #17222b; display: block; }
            .stats .etiqueta { font-size: 7.5pt; color: #567384; text-transform: uppercase; }
            h2 { font-size: 11pt; color: #17222b; border-bottom: 1pt solid #eb5a28; padding-bottom: 3px; margin-top: 18px; }
            table.datos { width: 100%; border-collapse: collapse; margin-top: 6px; }
            table.datos th { background: #17222b; color: #ffffff; font-size: 8.5pt; text-align: left; padding: 6px 8px; }
            table.datos td { font-size: 9pt; padding: 6px 8px; border-bottom: 0.5pt solid #dddddd; }
            table.datos td.num { text-align: right; }
            td.vacio { color: #96989a; text-align: center; font-style: italic; }
            .pie { margin-top: 24px; font-size: 7.5pt; color: #96989a; }
        </style>
        <div class="marca">Gasolinera El Cerron Grande &middot; Sistema POS</div>
        <h1>Reporte de operacion del dia</h1>
        <div class="subt">Generado el ' . htmlspecialchars($fecha) . '</div>

        <table class="stats">
            <tr>
                <td><span class="valor">$' . number_format($resumen['ventas_hoy'], 2) . '</span><span class="etiqueta">Ventas totales</span></td>
                <td><span class="valor">' . number_format($resumen['galones_despachados'], 1) . '</span><span class="etiqueta">Galones despachados</span></td>
                <td><span class="valor">' . (int) $resumen['productos_vendidos'] . '</span><span class="etiqueta">Productos vendidos</span></td>
                <td><span class="valor">' . (int) $resumen['turnos_cerrados'] . '</span><span class="etiqueta">Turnos cerrados</span></td>
            </tr>
        </table>

        <h2>Ventas por tipo de combustible</h2>
        <table class="datos">
            <thead><tr><th>Combustible</th><th>Galones</th><th>Total</th></tr></thead>
            <tbody>' . $filasCombustible . '</tbody>
        </table>

        <h2>Productos de tienda mas vendidos</h2>
        <table class="datos">
            <thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead>
            <tbody>' . $filasProductos . '</tbody>
        </table>

        <div class="pie">Documento generado automaticamente por el sistema POS. No requiere firma.</div>
        ';

        $mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_top' => 18, 'margin_bottom' => 15]);
        $mpdf->SetTitle('Reporte de operacion - ' . date('Y-m-d'));
        $mpdf->WriteHTML($html);
        $mpdf->Output('reporte-' . date('Y-m-d') . '.pdf', \Mpdf\Output\Destination::INLINE);
    }

    /** Genera el mismo reporte en CSV (se abre directo en Excel). */
    public static function generarCsv(): void
    {
        $resumen           = self::resumen();
        $ventasCombustible = self::ventasPorCombustible();
        $topProductos      = self::topProductos();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-' . date('Y-m-d') . '.csv"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel detecte UTF-8

        fputcsv($salida, ['Reporte de operacion del dia', date('d/m/Y H:i')]);
        fputcsv($salida, []);

        fputcsv($salida, ['Resumen del dia']);
        fputcsv($salida, ['Ventas totales', number_format($resumen['ventas_hoy'], 2)]);
        fputcsv($salida, ['Galones despachados', number_format($resumen['galones_despachados'], 1)]);
        fputcsv($salida, ['Productos vendidos', $resumen['productos_vendidos']]);
        fputcsv($salida, ['Turnos cerrados', $resumen['turnos_cerrados']]);
        fputcsv($salida, []);

        fputcsv($salida, ['Ventas por tipo de combustible']);
        fputcsv($salida, ['Combustible', 'Galones', 'Total']);
        foreach ($ventasCombustible as $v) {
            fputcsv($salida, [$v['combustible'], number_format($v['galones'], 1), number_format($v['total'], 2)]);
        }
        fputcsv($salida, []);

        fputcsv($salida, ['Productos de tienda mas vendidos']);
        fputcsv($salida, ['Producto', 'Unidades', 'Total']);
        foreach ($topProductos as $p) {
            fputcsv($salida, [$p['producto'], $p['unidades'], number_format($p['total'], 2)]);
        }

        fclose($salida);
    }
}
