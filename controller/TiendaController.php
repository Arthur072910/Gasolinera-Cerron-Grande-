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
    private const COMPROBANTES_FISCALES = ['factura', 'ccf'];

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
    public static function procesarVenta(
        array $carrito,
        string $metodoPago,
        string $tipoComprobante,
        int $idTurno,
        ?string $nombreCliente = null,
        ?string $nitCliente = null
    ): array {
        if (empty($carrito)) {
            throw new RuntimeException('El carrito esta vacio.');
        }

        $metodosValidos = ['efectivo', 'tarjeta', 'mixto'];
        if (!in_array($metodoPago, $metodosValidos, true)) {
            throw new RuntimeException('Metodo de pago invalido.');
        }

        $comprobantesValidos = ['ticket', 'factura', 'ccf'];
        if (!in_array($tipoComprobante, $comprobantesValidos, true)) {
            throw new RuntimeException('Tipo de comprobante invalido.');
        }

        $nombreCliente = trim((string) $nombreCliente);
        $nitCliente    = trim((string) $nitCliente);

        if (in_array($tipoComprobante, self::COMPROBANTES_FISCALES, true) && ($nombreCliente === '' || $nitCliente === '')) {
            throw new RuntimeException('Para factura o CCF debes indicar el nombre y el NIT/NRC del cliente.');
        }

        $conexion = Database::obtenerConexion();
        $lineas   = [];
        $total    = 0.0;

        // Consolida cantidades repetidas del mismo producto antes de
        // validar stock (el carrito nunca deberia mandar duplicados, pero
        // el servidor no confia en eso).
        $cantidadesPorProducto = [];
        foreach ($carrito as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            if ($idProducto <= 0) {
                continue;
            }
            $cantidadesPorProducto[$idProducto] = ($cantidadesPorProducto[$idProducto] ?? 0) + max(1, (int) ($item['cantidad'] ?? 0));
        }

        $conexion->beginTransaction();
        try {
            // El bloqueo de las filas de producto va DENTRO de la
            // transaccion y ANTES de leer su stock: asi, si dos ventas
            // del mismo producto llegan al mismo tiempo, la segunda
            // espera a que la primera confirme (o revierta) antes de leer
            // el stock, en vez de que ambas lean "antes" de la otra y las
            // dos pasen la validacion aunque juntas dejen el stock negativo.
            Producto::bloquearFilas($conexion, array_keys($cantidadesPorProducto));

            foreach ($cantidadesPorProducto as $idProducto => $cantidad) {
                $producto = Producto::obtenerPorId($conexion, $idProducto);
                if ($producto === null) {
                    continue;
                }
                if ($cantidad > (int) $producto['stock']) {
                    throw new RuntimeException(sprintf(
                        'No hay stock suficiente de "%s" (disponible: %d, solicitado: %d).',
                        $producto['nombre'],
                        (int) $producto['stock'],
                        $cantidad
                    ));
                }
                $subtotal = round($producto['precio'] * $cantidad, 2);
                $total   += $subtotal;
                $lineas[] = ['id_producto' => $producto['id'], 'nombre' => $producto['nombre'], 'cantidad' => $cantidad, 'precio' => $producto['precio'], 'subtotal' => $subtotal];
            }

            if (empty($lineas)) {
                throw new RuntimeException('Ninguno de los productos del carrito existe.');
            }

            $idVenta = Venta::crear(
                $conexion,
                $idTurno,
                $tipoComprobante,
                $metodoPago,
                round($total, 2),
                $nombreCliente !== '' ? $nombreCliente : null,
                $nitCliente !== '' ? $nitCliente : null
            );
            foreach ($lineas as $linea) {
                DetalleVentaTienda::crear($conexion, $idVenta, $linea['id_producto'], $linea['cantidad'], $linea['precio'], $linea['subtotal']);
                Producto::descontarStock($conexion, $linea['id_producto'], $linea['cantidad']);
            }
            $conexion->commit();
        } catch (Exception $e) {
            $conexion->rollBack();
            throw $e;
        }

        return [
            'id_venta'           => $idVenta,
            'numero_comprobante' => Venta::formatearNumeroComprobante($tipoComprobante, $idVenta),
            'tipo_comprobante'   => $tipoComprobante,
            'metodo_pago'        => $metodoPago,
            'nombre_cliente'     => $nombreCliente !== '' ? $nombreCliente : null,
            'nit_cliente'        => $nitCliente !== '' ? $nitCliente : null,
            'fecha'              => date('Y-m-d H:i:s'),
            'total'              => round($total, 2),
            'lineas'             => $lineas,
        ];
    }
}
