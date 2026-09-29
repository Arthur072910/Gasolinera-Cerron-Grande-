<?php
/**
 * Modelo: DetalleVentaCombustible
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class DetalleVentaCombustible
{
    public $id_detalle_combustible;
    public $id_venta;
    public $id_manguera;
    public $galones_despachados;
    public $precio_galon_aplicado;
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
        int $idManguera,
        float $galones,
        float $precioGalon,
        float $subtotal
    ): void {
        $stmt = $conexion->prepare(
            'INSERT INTO detalle_ventas_combustible (id_venta, id_manguera, galones_despachados, precio_galon_aplicado, subtotal)
             VALUES (:id_venta, :id_manguera, :galones, :precio, :subtotal)'
        );
        $stmt->execute([
            ':id_venta'    => $idVenta,
            ':id_manguera' => $idManguera,
            ':galones'     => $galones,
            ':precio'      => $precioGalon,
            ':subtotal'    => $subtotal,
        ]);
    }

    public static function resumenPorCombustibleHoy(PDO $conexion): array
    {
        // La fecha se filtra ANTES de unir con mangueras/tanques (subconsulta),
        // no en la condicion del ultimo LEFT JOIN: con el filtro ahi, una
        // venta de ayer sin coincidir con CURDATE() solo anulaba las
        // columnas de `v`, pero la fila de `d` (galones/subtotal) ya
        // habia quedado unida por manguera sin importar la fecha, y se
        // sumaba igual. Esa es la causa de que ventas de dias anteriores
        // aparecieran en el resumen de "hoy".
        $sql = "SELECT t.tipo_combustible AS combustible,
                       COALESCE(SUM(x.galones_despachados), 0) AS galones,
                       COALESCE(SUM(x.subtotal), 0) AS total
                FROM mangueras m
                JOIN tanques t ON t.id_tanque = m.id_tanque
                LEFT JOIN (
                    SELECT d.id_manguera, d.galones_despachados, d.subtotal
                    FROM detalle_ventas_combustible d
                    JOIN ventas v ON v.id_venta = d.id_venta
                    WHERE DATE(v.fecha_hora) = CURDATE()
                ) x ON x.id_manguera = m.id_manguera
                GROUP BY t.tipo_combustible
                ORDER BY t.tipo_combustible";
        $filas = $conexion->query($sql)->fetchAll();
        foreach ($filas as &$f) {
            $f['combustible'] = ucfirst($f['combustible']);
        }
        return $filas;
    }

    public static function totalGalonesHoy(PDO $conexion): float
    {
        $sql = "SELECT COALESCE(SUM(d.galones_despachados), 0)
                FROM detalle_ventas_combustible d
                JOIN ventas v ON v.id_venta = d.id_venta
                WHERE DATE(v.fecha_hora) = CURDATE()";
        return (float) $conexion->query($sql)->fetchColumn();
    }
}
