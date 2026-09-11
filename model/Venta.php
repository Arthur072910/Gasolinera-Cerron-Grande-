<?php
/**
 * Modelo: Venta
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Venta
{
    public $id_venta;
    public $id_turno;
    public $tipo_comprobante;
    public $numero_comprobante;
    public $monto_total;
    public $metodo_pago;
    public $fecha_hora;

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
        int $idTurno,
        string $tipoComprobante,
        string $metodoPago,
        float $montoTotal
    ): int {
        $stmt = $conexion->prepare(
            "INSERT INTO ventas (id_turno, tipo_comprobante, numero_comprobante, monto_total, metodo_pago)
             VALUES (:id_turno, :tipo_comprobante, 'PENDIENTE', :monto_total, :metodo_pago)"
        );
        $stmt->execute([
            ':id_turno'         => $idTurno,
            ':tipo_comprobante' => $tipoComprobante,
            ':monto_total'      => $montoTotal,
            ':metodo_pago'      => $metodoPago,
        ]);
        $idVenta = (int) $conexion->lastInsertId();

        $prefijo = ['ticket' => 'TCK', 'factura' => 'FAC', 'ccf' => 'CCF'][$tipoComprobante] ?? 'TCK';
        $numero  = $prefijo . '-' . date('Ymd') . '-' . str_pad((string) $idVenta, 6, '0', STR_PAD_LEFT);
        $conexion->prepare('UPDATE ventas SET numero_comprobante = :numero WHERE id_venta = :id')
            ->execute([':numero' => $numero, ':id' => $idVenta]);

        return $idVenta;
    }

    public static function totalHoy(PDO $conexion): float
    {
        return (float) $conexion->query('SELECT COALESCE(SUM(monto_total), 0) FROM ventas WHERE DATE(fecha_hora) = CURDATE()')->fetchColumn();
    }
}
