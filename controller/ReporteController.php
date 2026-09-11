<?php
/**
 * Controlador: Reportes gerenciales
 * Consultas agregadas sobre `ventas`, `detalle_ventas_combustible` y
 * `detalle_ventas_tienda`, todas acotadas al dia en curso.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Venta.php';
require_once __DIR__ . '/../model/DetalleVentaCombustible.php';
require_once __DIR__ . '/../model/DetalleVentaTienda.php';
require_once __DIR__ . '/../model/Turno.php';

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
}
