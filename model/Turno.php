<?php
/**
 * Modelo: Turno
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Turno
{
    public $id_turno;
    public $id_asistencia;
    public $tipo_caja;
    public $fecha_inicio;
    public $fecha_fin;
    public $monto_inicial;
    public $monto_declarado;
    public $estado;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerAbiertoPorUsuario(PDO $conexion, int $idUsuario): ?array
    {
        $sql = "SELECT t.* FROM turnos t
                JOIN asistencia a ON a.id_asistencia = t.id_asistencia
                WHERE a.id_usuario = :id_usuario AND t.estado = 'abierto'
                ORDER BY t.id_turno DESC LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function abrir(PDO $conexion, int $idAsistencia, string $tipoCaja, float $montoInicial = 20.00): int
    {
        $stmt = $conexion->prepare(
            'INSERT INTO turnos (id_asistencia, tipo_caja, monto_inicial) VALUES (:id_asistencia, :tipo_caja, :monto_inicial)'
        );
        $stmt->execute([
            ':id_asistencia' => $idAsistencia,
            ':tipo_caja'     => $tipoCaja,
            ':monto_inicial' => $montoInicial,
        ]);
        return (int) $conexion->lastInsertId();
    }

    public static function cerrar(PDO $conexion, int $idTurno, float $montoDeclarado): void
    {
        $stmt = $conexion->prepare(
            "UPDATE turnos SET fecha_fin = NOW(), monto_declarado = :monto, estado = 'cerrado' WHERE id_turno = :id"
        );
        $stmt->execute([':monto' => $montoDeclarado, ':id' => $idTurno]);
    }

    public static function obtenerUltimoPorUsuario(PDO $conexion, int $idUsuario, string $tipoCaja): ?array
    {
        $sql = 'SELECT t.* FROM turnos t
                JOIN asistencia a ON a.id_asistencia = t.id_asistencia
                WHERE a.id_usuario = :id_usuario AND t.tipo_caja = :tipo_caja
                ORDER BY t.id_turno DESC LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario, ':tipo_caja' => $tipoCaja]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function contarCerradosHoy(PDO $conexion): int
    {
        $sql = "SELECT COUNT(*) FROM turnos WHERE estado = 'cerrado' AND DATE(fecha_fin) = CURDATE()";
        return (int) $conexion->query($sql)->fetchColumn();
    }

    public static function obtenerVentasPorTipoPago(PDO $conexion, int $idTurno): array
    {
        $sql = "SELECT metodo_pago, COALESCE(SUM(monto_total), 0) AS total
                FROM ventas WHERE id_turno = :id_turno GROUP BY metodo_pago";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id_turno' => $idTurno]);
        return $stmt->fetchAll();
    }

    public static function totalVentas(PDO $conexion, int $idTurno): float
    {
        $stmt = $conexion->prepare('SELECT COALESCE(SUM(monto_total), 0) FROM ventas WHERE id_turno = :id_turno');
        $stmt->execute([':id_turno' => $idTurno]);
        return (float) $stmt->fetchColumn();
    }
}
