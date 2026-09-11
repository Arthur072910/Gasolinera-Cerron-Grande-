<?php
/**
 * Controlador: Inventario y Kardex de tienda
 * Conectado a `productos` (alertas de stock minimo) y a
 * `detalle_ventas_tienda` (salidas reales por venta).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Producto.php';
require_once __DIR__ . '/../model/DetalleVentaTienda.php';

class InventarioController
{
    public static function alertasStock(): array
    {
        return Producto::obtenerBajoStock(Database::obtenerConexion());
    }

    public static function movimientosKardex(int $limite = 30): array
    {
        return DetalleVentaTienda::obtenerMovimientosRecientes(Database::obtenerConexion(), $limite);
    }
}
