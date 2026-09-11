<?php
/**
 * Modelo: RecepcionCisterna
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class RecepcionCisterna
{
    public $id_recepcion;
    public $id_proveedor;
    public $id_tanque;
    public $numero_factura;
    public $galones_facturados;
    public $galones_medidos_tanque;
    public $costo_total;
    public $fecha_hora;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerTodasConDetalle(PDO $conexion): array
    {
        $sql = "SELECT p.nombre_empresa AS proveedor, t.tipo_combustible AS combustible,
                       r.galones_facturados, r.galones_medidos_tanque AS galones_medidos, r.fecha_hora AS fecha
                FROM recepcion_cisternas r
                JOIN proveedores p ON p.id_proveedor = r.id_proveedor
                JOIN tanques t ON t.id_tanque = r.id_tanque
                ORDER BY r.fecha_hora DESC";
        $filas = $conexion->query($sql)->fetchAll();
        foreach ($filas as &$f) {
            $f['combustible'] = ucfirst($f['combustible']);
        }
        return $filas;
    }

    public static function crear(
        PDO $conexion,
        int $idProveedor,
        int $idTanque,
        string $numeroFactura,
        float $galonesFacturados,
        float $galonesMedidos,
        float $costoTotal
    ): int {
        $stmt = $conexion->prepare(
            'INSERT INTO recepcion_cisternas (id_proveedor, id_tanque, numero_factura, galones_facturados, galones_medidos_tanque, costo_total)
             VALUES (:id_proveedor, :id_tanque, :numero_factura, :facturados, :medidos, :costo)'
        );
        $stmt->execute([
            ':id_proveedor'   => $idProveedor,
            ':id_tanque'      => $idTanque,
            ':numero_factura' => $numeroFactura,
            ':facturados'     => $galonesFacturados,
            ':medidos'        => $galonesMedidos,
            ':costo'          => $costoTotal,
        ]);
        return (int) $conexion->lastInsertId();
    }
}
