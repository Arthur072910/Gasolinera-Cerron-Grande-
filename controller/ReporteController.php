<?php
/**
 * Controlador: Reportes gerenciales
 * Consultas agregadas sobre `ventas`, `detalle_ventas_combustible` y
 * `detalle_ventas_tienda`, todas acotadas al dia en curso. Tambien
 * genera la exportacion en PDF (mPDF) y en Excel real (.xlsx, via
 * PhpSpreadsheet), ver composer.json.
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
        $fecha             = date('d/m/Y \a \l\a\s H:i');

        $filasCombustible = '';
        foreach ($ventasCombustible as $i => $v) {
            $filasCombustible .= '<tr class="' . ($i % 2 === 1 ? 'par' : '') . '">'
                . '<td class="marca-fila">' . htmlspecialchars($v['combustible']) . '</td>'
                . '<td class="num">' . number_format($v['galones'], 1) . ' gal</td>'
                . '<td class="num num-fuerte">$' . number_format($v['total'], 2) . '</td>'
                . '</tr>';
        }
        if ($ventasCombustible === []) {
            $filasCombustible = '<tr><td colspan="3" class="vacio">Sin ventas de combustible registradas hoy.</td></tr>';
        }

        $filasProductos = '';
        foreach ($topProductos as $i => $p) {
            $filasProductos .= '<tr class="' . ($i % 2 === 1 ? 'par' : '') . '">'
                . '<td class="marca-fila">' . htmlspecialchars($p['producto']) . '</td>'
                . '<td class="num">' . $p['unidades'] . '</td>'
                . '<td class="num num-fuerte">$' . number_format($p['total'], 2) . '</td>'
                . '</tr>';
        }
        if ($topProductos === []) {
            $filasProductos = '<tr><td colspan="3" class="vacio">Sin ventas de tienda registradas hoy.</td></tr>';
        }

        $html = '
        <style>
            body { font-family: sans-serif; color: #23303a; font-size: 10pt; }

            .cintillo { background: #17222b; height: 5pt; margin: -18pt -18pt 16pt -18pt; }

            .marca { color: #eb5a28; font-weight: bold; font-size: 8.5pt; letter-spacing: 2pt; text-transform: uppercase; }
            h1 { font-size: 19pt; color: #17222b; margin: 4pt 0 3pt; font-weight: bold; }
            .subt { color: #567384; font-size: 9pt; margin-bottom: 18pt; }

            .stats { width: 100%; border-collapse: separate; border-spacing: 6pt 0; margin: 0 0 20pt -6pt; }
            .stats td {
                width: 25%; background: #f5f6f7; border: 0.75pt solid #e3e6e8; border-left: 2.5pt solid #eb5a28;
                border-radius: 3pt; padding: 10pt 11pt;
            }
            .stats .valor { display: block; font-size: 15pt; font-weight: bold; color: #17222b; line-height: 1.2; }
            .stats .etiqueta { display: block; font-size: 7pt; color: #567384; text-transform: uppercase; letter-spacing: 0.4pt; margin-top: 4pt; }

            h2 {
                font-size: 10.5pt; color: #17222b; text-transform: uppercase; letter-spacing: 0.3pt;
                margin: 20pt 0 8pt; padding-left: 10pt; border-left: 3pt solid #eb5a28;
            }

            table.datos { width: 100%; border-collapse: collapse; margin-top: 2pt; }
            table.datos th {
                background: #17222b; color: #ffffff; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3pt;
                text-align: left; padding: 7pt 9pt;
            }
            table.datos th.num { text-align: right; }
            table.datos td { font-size: 9pt; padding: 7pt 9pt; border-bottom: 0.5pt solid #e6e8ea; }
            table.datos tr.par td { background: #f7f8f9; }
            table.datos td.num { text-align: right; }
            table.datos td.num-fuerte { font-weight: bold; color: #17222b; }
            table.datos td.marca-fila { color: #23303a; }
            td.vacio { color: #96989a; text-align: center; font-style: italic; padding: 14pt 0; }

            .pie { margin-top: 26pt; padding-top: 8pt; border-top: 0.5pt solid #e3e6e8; font-size: 7.5pt; color: #96989a; }
        </style>
        <div class="cintillo"></div>
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
            <thead><tr><th>Combustible</th><th class="num">Galones</th><th class="num">Total</th></tr></thead>
            <tbody>' . $filasCombustible . '</tbody>
        </table>

        <h2>Productos de tienda mas vendidos</h2>
        <table class="datos">
            <thead><tr><th>Producto</th><th class="num">Unidades</th><th class="num">Total</th></tr></thead>
            <tbody>' . $filasProductos . '</tbody>
        </table>

        <div class="pie">Documento generado automaticamente por el sistema POS &middot; No requiere firma.</div>
        ';

        $mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'margin_top' => 18, 'margin_bottom' => 15, 'margin_left' => 18, 'margin_right' => 18]);
        $mpdf->SetTitle('Reporte de operacion - ' . date('Y-m-d'));
        $mpdf->SetFooter('Gasolinera El Cerron Grande &middot; {PAGENO} / {nbpg}');
        $mpdf->WriteHTML($html);
        $mpdf->Output('reporte-' . date('Y-m-d') . '.pdf', \Mpdf\Output\Destination::INLINE);
    }

    /** Genera el mismo reporte en un Excel real (.xlsx) con formato, via PhpSpreadsheet. */
    public static function generarExcel(): void
    {
        $resumen           = self::resumen();
        $ventasCombustible = self::ventasPorCombustible();
        $topProductos      = self::topProductos();

        $naranja = 'EB5A28';
        $void    = '17222B';
        $pizarra = '567384';
        $ceniza  = '96989A';
        $franja  = 'F5F6F7';
        $borde   = 'E3E6E8';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Reporte de operacion - ' . date('Y-m-d'))
            ->setCreator('Sistema POS - Gasolinera El Cerron Grande');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte del dia');
        $sheet->setShowGridlines(false);
        foreach (['A' => 26, 'B' => 20, 'C' => 20] as $col => $ancho) {
            $sheet->getColumnDimension($col)->setWidth($ancho);
        }

        $fila = 1;

        // ---- Encabezado ----
        $sheet->setCellValue("A{$fila}", 'GASOLINERA EL CERRON GRANDE · SISTEMA POS');
        $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($naranja);
        $fila++;

        $sheet->setCellValue("A{$fila}", 'Reporte de operacion del dia');
        $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(16)->getColor()->setRGB($void);
        $fila++;

        $sheet->setCellValue("A{$fila}", 'Generado el ' . date('d/m/Y \a \l\a\s H:i'));
        $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB($pizarra);
        $fila += 2;

        // ---- Resumen del dia ----
        $sheet->setCellValue("A{$fila}", 'RESUMEN DEL DIA');
        self::estilizarTituloSeccion($sheet, "A{$fila}", $naranja, $void);
        $fila++;

        $resumenFilas = [
            ['Ventas totales', '$' . number_format($resumen['ventas_hoy'], 2)],
            ['Galones despachados', number_format($resumen['galones_despachados'], 1) . ' gal'],
            ['Productos vendidos', (int) $resumen['productos_vendidos']],
            ['Turnos cerrados', (int) $resumen['turnos_cerrados']],
        ];
        foreach ($resumenFilas as [$etiqueta, $valor]) {
            $sheet->setCellValue("A{$fila}", $etiqueta);
            $sheet->setCellValue("B{$fila}", $valor);
            $sheet->getStyle("A{$fila}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($pizarra));
            $sheet->getStyle("B{$fila}")->getFont()->setBold(true)->getColor()->setRGB($void);
            $fila++;
        }
        $fila++;

        // ---- Ventas por tipo de combustible ----
        $sheet->setCellValue("A{$fila}", 'VENTAS POR TIPO DE COMBUSTIBLE');
        self::estilizarTituloSeccion($sheet, "A{$fila}", $naranja, $void);
        $fila++;

        $filaEncabezado = $fila;
        $sheet->setCellValue("A{$fila}", 'Combustible');
        $sheet->setCellValue("B{$fila}", 'Galones');
        $sheet->setCellValue("C{$fila}", 'Total');
        self::estilizarEncabezadoTabla($sheet, "A{$fila}:C{$fila}", $void);
        $fila++;

        if ($ventasCombustible === []) {
            $sheet->setCellValue("A{$fila}", 'Sin ventas de combustible registradas hoy.');
            $sheet->mergeCells("A{$fila}:C{$fila}");
            $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->getColor()->setRGB($ceniza);
            $fila++;
        } else {
            foreach ($ventasCombustible as $i => $v) {
                $sheet->setCellValue("A{$fila}", $v['combustible']);
                $sheet->setCellValue("B{$fila}", round((float) $v['galones'], 1));
                $sheet->setCellValue("C{$fila}", round((float) $v['total'], 2));
                $sheet->getStyle("C{$fila}")->getFont()->setBold(true);
                if ($i % 2 === 1) {
                    self::rellenarFila($sheet, "A{$fila}:C{$fila}", $franja);
                }
                $fila++;
            }
        }
        self::bordearRango($sheet, "A{$filaEncabezado}:C" . ($fila - 1), $borde);
        $sheet->getStyle('B' . ($filaEncabezado + 1) . ':B' . ($fila - 1))->getNumberFormat()->setFormatCode('0.0" gal"');
        $sheet->getStyle('C' . ($filaEncabezado + 1) . ':C' . ($fila - 1))->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $fila++;

        // ---- Productos de tienda mas vendidos ----
        $sheet->setCellValue("A{$fila}", 'PRODUCTOS DE TIENDA MAS VENDIDOS');
        self::estilizarTituloSeccion($sheet, "A{$fila}", $naranja, $void);
        $fila++;

        $filaEncabezado = $fila;
        $sheet->setCellValue("A{$fila}", 'Producto');
        $sheet->setCellValue("B{$fila}", 'Unidades');
        $sheet->setCellValue("C{$fila}", 'Total');
        self::estilizarEncabezadoTabla($sheet, "A{$fila}:C{$fila}", $void);
        $fila++;

        if ($topProductos === []) {
            $sheet->setCellValue("A{$fila}", 'Sin ventas de tienda registradas hoy.');
            $sheet->mergeCells("A{$fila}:C{$fila}");
            $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->getColor()->setRGB($ceniza);
            $fila++;
        } else {
            foreach ($topProductos as $i => $p) {
                $sheet->setCellValue("A{$fila}", $p['producto']);
                $sheet->setCellValue("B{$fila}", (int) $p['unidades']);
                $sheet->setCellValue("C{$fila}", round((float) $p['total'], 2));
                $sheet->getStyle("C{$fila}")->getFont()->setBold(true);
                if ($i % 2 === 1) {
                    self::rellenarFila($sheet, "A{$fila}:C{$fila}", $franja);
                }
                $fila++;
            }
        }
        self::bordearRango($sheet, "A{$filaEncabezado}:C" . ($fila - 1), $borde);
        $sheet->getStyle('C' . ($filaEncabezado + 1) . ':C' . ($fila - 1))->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $fila += 2;

        $sheet->setCellValue("A{$fila}", 'Documento generado automaticamente por el sistema POS. No requiere firma.');
        $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB($ceniza);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="reporte-' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    private static function estilizarTituloSeccion(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $celda, string $naranja, string $void): void
    {
        $sheet->getStyle($celda)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB($void);
        $sheet->getStyle($celda)->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK)->getColor()->setRGB($naranja);
    }

    private static function estilizarEncabezadoTabla(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rango, string $void): void
    {
        $estilo = $sheet->getStyle($rango);
        $estilo->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estilo->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($void);
    }

    private static function rellenarFila(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rango, string $color): void
    {
        $sheet->getStyle($rango)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($color);
    }

    private static function bordearRango(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rango, string $color): void
    {
        $sheet->getStyle($rango)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setRGB($color);
    }
}
