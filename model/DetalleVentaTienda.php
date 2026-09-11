<?php
/**
 * Modelo: DetalleVentaTienda
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class DetalleVentaTienda
{
    public $id_detalle_tienda;
    public $id_venta;
    public $id_producto;
    public $cantidad;
    public $precio_unitario;
    public $subtotal;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function crear(
        PDO $conexion,
        int $idVenta,
        int $idProducto,
        int $cantidad,
        float $precioUnitario,
        float $subtotal
    ): void {
        $stmt = $conexion->prepare(
            'INSERT INTO detalle_ventas_tienda (id_venta, id_producto, cantidad, precio_unitario, subtotal)
             VALUES (:id_venta, :id_producto, :cantidad, :precio, :subtotal)'
        );
        $stmt->execute([
            ':id_venta'    => $idVenta,
            ':id_producto' => $idProducto,
            ':cantidad'    => $cantidad,
            ':precio'      => $precioUnitario,
            ':subtotal'    => $subtotal,
        ]);
    }

    public static function obtenerMovimientosRecientes(PDO $conexion, int $limite = 20): array
    {
        $sql = "SELECT v.fecha_hora AS fecha, p.nombre_producto AS producto, 'salida' AS tipo,
                       d.cantidad, 'Venta en tienda' AS motivo
                FROM detalle_ventas_tienda d
                JOIN productos p ON p.id_producto = d.id_producto
                JOIN ventas v ON v.id_venta = d.id_venta
                ORDER BY v.fecha_hora DESC
                LIMIT " . max(1, $limite);
        return $conexion->query($sql)->fetchAll();
    }

    public static function obtenerTopProductosHoy(PDO $conexion, int $limite = 5): array
    {
        $sql = "SELECT p.nombre_producto AS producto, SUM(d.cantidad) AS unidades, SUM(d.subtotal) AS total
                FROM detalle_ventas_tienda d
                JOIN productos p ON p.id_producto = d.id_producto
                JOIN ventas v ON v.id_venta = d.id_venta
                WHERE DATE(v.fecha_hora) = CURDATE()
                GROUP BY p.id_producto
                ORDER BY unidades DESC
                LIMIT " . max(1, $limite);
        return $conexion->query($sql)->fetchAll();
    }

    public static function totalUnidadesHoy(PDO $conexion): int
    {
        $sql = "SELECT COALESCE(SUM(d.cantidad), 0)
                FROM detalle_ventas_tienda d
                JOIN ventas v ON v.id_venta = d.id_venta
                WHERE DATE(v.fecha_hora) = CURDATE()";
        return (int) $conexion->query($sql)->fetchColumn();
    }
}
