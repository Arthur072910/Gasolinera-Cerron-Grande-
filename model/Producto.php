<?php
/**
 * Modelo: Producto
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Producto
{
    public $id_producto;
    public $id_categoria;
    public $codigo_barras;
    public $nombre_producto;
    public $precio_venta;
    public $stock_actual;
    public $stock_minimo;
    public $fecha_vencimiento;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerTodos(PDO $conexion): array
    {
        $sql = "SELECT id_producto AS id, id_categoria, codigo_barras,
                       nombre_producto AS nombre, precio_venta AS precio, stock_actual AS stock
                FROM productos ORDER BY nombre_producto";
        return $conexion->query($sql)->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id): ?array
    {
        $stmt = $conexion->prepare(
            'SELECT id_producto AS id, nombre_producto AS nombre, precio_venta AS precio, stock_actual AS stock
             FROM productos WHERE id_producto = :id'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function obtenerBajoStock(PDO $conexion): array
    {
        $sql = "SELECT nombre_producto AS producto, stock_actual, stock_minimo
                FROM productos WHERE stock_actual <= stock_minimo
                ORDER BY (stock_actual - stock_minimo)";
        return $conexion->query($sql)->fetchAll();
    }

    public static function descontarStock(PDO $conexion, int $idProducto, int $cantidad): void
    {
        $stmt = $conexion->prepare(
            'UPDATE productos SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id'
        );
        $stmt->execute([':cantidad' => $cantidad, ':id' => $idProducto]);
    }

    public static function crear(
        PDO $conexion,
        int $idCategoria,
        ?string $codigoBarras,
        string $nombre,
        float $precio,
        int $stockInicial,
        int $stockMinimo
    ): int {
        $stmt = $conexion->prepare(
            'INSERT INTO productos (id_categoria, codigo_barras, nombre_producto, precio_venta, stock_actual, stock_minimo)
             VALUES (:id_categoria, :codigo_barras, :nombre, :precio, :stock, :stock_minimo)'
        );
        $stmt->execute([
            ':id_categoria'  => $idCategoria,
            ':codigo_barras' => $codigoBarras !== '' ? $codigoBarras : null,
            ':nombre'        => $nombre,
            ':precio'        => $precio,
            ':stock'         => $stockInicial,
            ':stock_minimo'  => $stockMinimo,
        ]);
        return (int) $conexion->lastInsertId();
    }
}
