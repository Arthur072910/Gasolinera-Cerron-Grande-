<?php
/**
 * Controlador: Inventario y Kardex de tienda
 * Conectado a `productos` (catalogo, alertas de stock minimo) y a
 * `detalle_ventas_tienda` (salidas reales por venta).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Producto.php';
require_once __DIR__ . '/../model/Categoria.php';
require_once __DIR__ . '/../model/DetalleVentaTienda.php';

class InventarioController
{
    public static function productos(): array
    {
        return Producto::obtenerTodosDetalle(Database::obtenerConexion());
    }

    public static function categorias(): array
    {
        return Categoria::obtenerTodas(Database::obtenerConexion());
    }

    public static function alertasStock(): array
    {
        return Producto::obtenerBajoStock(Database::obtenerConexion());
    }

    public static function movimientosKardex(int $limite = 30): array
    {
        return DetalleVentaTienda::obtenerMovimientosRecientes(Database::obtenerConexion(), $limite);
    }

    public static function registrarProducto(
        int $idCategoria,
        string $codigoBarras,
        string $nombre,
        float $precio,
        int $stockInicial,
        int $stockMinimo
    ): void {
        self::validarProducto($nombre, $precio, $stockMinimo);
        if ($stockInicial < 0) {
            throw new RuntimeException('El stock inicial no puede ser negativo.');
        }
        try {
            Producto::crear(Database::obtenerConexion(), $idCategoria, trim($codigoBarras), trim($nombre), $precio, $stockInicial, $stockMinimo);
        } catch (PDOException $e) {
            throw self::traducirErrorUnico($e);
        }
    }

    public static function editarProducto(
        int $idProducto,
        int $idCategoria,
        string $codigoBarras,
        string $nombre,
        float $precio,
        int $stockMinimo
    ): void {
        if ($idProducto <= 0) {
            throw new RuntimeException('Producto invalido.');
        }
        self::validarProducto($nombre, $precio, $stockMinimo);
        try {
            Producto::actualizar(Database::obtenerConexion(), $idProducto, $idCategoria, trim($codigoBarras), trim($nombre), $precio, $stockMinimo);
        } catch (PDOException $e) {
            throw self::traducirErrorUnico($e);
        }
    }

    /** $cantidad puede ser negativa (correccion a la baja), pero nunca deja el stock en negativo. */
    public static function ajustarStock(int $idProducto, int $cantidad): void
    {
        if ($cantidad === 0) {
            throw new RuntimeException('Ingresa una cantidad distinta de cero.');
        }
        $conexion = Database::obtenerConexion();
        $producto = Producto::obtenerPorId($conexion, $idProducto);
        if ($producto === null) {
            throw new RuntimeException('El producto ya no existe.');
        }
        if ($producto['stock'] + $cantidad < 0) {
            throw new RuntimeException('Esa cantidad dejaria el stock en negativo (actual: ' . $producto['stock'] . ').');
        }
        Producto::ajustarStock($conexion, $idProducto, $cantidad);
    }

    public static function eliminarProducto(int $idProducto): void
    {
        try {
            Producto::eliminar(Database::obtenerConexion(), $idProducto);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                throw new RuntimeException('No se puede eliminar: este producto ya tiene ventas registradas.');
            }
            throw $e;
        }
    }

    public static function registrarCategoria(string $nombre): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('El nombre de la categoria es obligatorio.');
        }
        Categoria::crear(Database::obtenerConexion(), $nombre);
    }

    private static function validarProducto(string $nombre, float $precio, int $stockMinimo): void
    {
        if (trim($nombre) === '') {
            throw new RuntimeException('El nombre del producto es obligatorio.');
        }
        if ($precio <= 0) {
            throw new RuntimeException('El precio de venta debe ser mayor a cero.');
        }
        if ($stockMinimo < 0) {
            throw new RuntimeException('El stock minimo no puede ser negativo.');
        }
    }

    private static function traducirErrorUnico(PDOException $e): RuntimeException
    {
        if ((int) $e->getCode() === 23000) {
            return new RuntimeException('Ya existe otro producto registrado con ese codigo de barras.');
        }
        return new RuntimeException('Ocurrio un error al guardar el producto.');
    }
}
