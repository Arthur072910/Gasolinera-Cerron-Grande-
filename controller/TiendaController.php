<?php
/**
 * Controlador: Ventas de Tienda de Conveniencia (POS Cajero)
 * Conectado a `productos`, `categorias`, `ventas` y `detalle_ventas_tienda`.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Categoria.php';
require_once __DIR__ . '/../model/Producto.php';
require_once __DIR__ . '/../model/Venta.php';
require_once __DIR__ . '/../model/DetalleVentaTienda.php';

class TiendaController
{
    public static function categorias(): array
    {
        return Categoria::obtenerTodas(Database::obtenerConexion());
    }

    public static function productos(): array
    {
        return Producto::obtenerTodos(Database::obtenerConexion());
    }

    public static function metodosPago(): array
    {
        return [
            ['id' => 'efectivo', 'nombre' => 'Efectivo'],
            ['id' => 'tarjeta',  'nombre' => 'Tarjeta'],
            ['id' => 'mixto',    'nombre' => 'Mixto'],
        ];
    }

    public static function tiposComprobante(): array
    {
        return [
            ['id' => 'ticket',  'nombre' => 'Ticket simple'],
            ['id' => 'factura', 'nombre' => 'Factura de consumidor final'],
            ['id' => 'ccf',     'nombre' => 'Comprobante de Credito Fiscal'],
        ];
    }

    /**
     * $carrito: [ [id_producto, cantidad], ... ]
     * El precio SIEMPRE se relee de `productos` en el servidor (nunca
     * del formulario) para no confiar en el precio que viajo del navegador.
     */
    public static function procesarVenta(array $carrito, string $metodoPago, string $tipoComprobante, int $idTurno): array
    {
        if (empty($carrito)) {
            throw new RuntimeException('El carrito esta vacio.');
        }

        $conexion = Database::obtenerConexion();
        $lineas   = [];
        $total    = 0.0;

        foreach ($carrito as $item) {
            $producto = Producto::obtenerPorId($conexion, (int) $item['id_producto']);
            if ($producto === null) {
                continue;
            }
            $cantidad = max(1, (int) $item['cantidad']);
            if ($cantidad > (int) $producto['stock']) {
                throw new RuntimeException('No hay stock suficiente de "' . $producto['nombre'] . '".');
            }
            $subtotal = round($producto['precio'] * $cantidad, 2);
            $total   += $subtotal;
            $lineas[] = ['id_producto' => $producto['id'], 'nombre' => $producto['nombre'], 'cantidad' => $cantidad, 'precio' => $producto['precio'], 'subtotal' => $subtotal];
        }

        if (empty($lineas)) {
            throw new RuntimeException('Ninguno de los productos del carrito existe.');
        }

        $conexion->beginTransaction();
        try {
            $idVenta = Venta::crear($conexion, $idTurno, $tipoComprobante, $metodoPago, round($total, 2));
            foreach ($lineas as $linea) {
                DetalleVentaTienda::crear($conexion, $idVenta, $linea['id_producto'], $linea['cantidad'], $linea['precio'], $linea['subtotal']);
                Producto::descontarStock($conexion, $linea['id_producto'], $linea['cantidad']);
            }
            $conexion->commit();
        } catch (Exception $e) {
            $conexion->rollBack();
            throw $e;
        }

        return ['id_venta' => $idVenta, 'total' => round($total, 2), 'lineas' => $lineas];
    }
}
