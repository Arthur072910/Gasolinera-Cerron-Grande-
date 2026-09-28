<?php
/**
 * Modelo: Asistencia
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Asistencia
{
    public $id_asistencia;
    public $id_usuario;
    public $fecha_hora_entrada;
    public $fecha_hora_salida;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function registrarEntrada(PDO $conexion, int $idUsuario): int
    {
        $stmt = $conexion->prepare('INSERT INTO asistencia (id_usuario) VALUES (:id_usuario)');
        $stmt->execute([':id_usuario' => $idUsuario]);
        return (int) $conexion->lastInsertId();
    }

    public static function registrarSalida(PDO $conexion, int $idAsistencia): void
    {
        $stmt = $conexion->prepare(
            'UPDATE asistencia SET fecha_hora_salida = NOW()
             WHERE id_asistencia = :id AND fecha_hora_salida IS NULL'
        );
        $stmt->execute([':id' => $idAsistencia]);
    }

    /**
     * TODAS las sesiones de asistencia abiertas (sin salida) de ese
     * usuario, mas reciente primero. En condiciones normales deberia
     * haber a lo sumo una; si hay varias (quedaron abandonadas por
     * cerrar la pestana sin salir, reinicios del servidor, etc.) se
     * usa para depurarlas al iniciar sesion de nuevo.
     */
    public static function obtenerTodasAbiertasPorUsuario(PDO $conexion, int $idUsuario): array
    {
        $stmt = $conexion->prepare(
            'SELECT id_asistencia, fecha_hora_entrada FROM asistencia
             WHERE id_usuario = :id_usuario AND fecha_hora_salida IS NULL
             ORDER BY id_asistencia DESC'
        );
        $stmt->execute([':id_usuario' => $idUsuario]);
        return $stmt->fetchAll();
    }

    /** Cierra una sesion con una fecha de salida especifica (no NOW()). */
    public static function cerrarConFecha(PDO $conexion, int $idAsistencia, string $fechaHoraSalida): void
    {
        $stmt = $conexion->prepare(
            'UPDATE asistencia SET fecha_hora_salida = :salida
             WHERE id_asistencia = :id AND fecha_hora_salida IS NULL'
        );
        $stmt->execute([':salida' => $fechaHoraSalida, ':id' => $idAsistencia]);
    }

    public static function obtenerTodasConUsuario(PDO $conexion): array
    {
        $sql = 'SELECT a.fecha_hora_entrada, a.fecha_hora_salida, u.nombre AS usuario, r.nombre_rol AS rol
                FROM asistencia a
                JOIN usuarios u ON u.id_usuario = a.id_usuario
                JOIN roles r ON r.id_rol = u.id_rol
                ORDER BY a.fecha_hora_entrada DESC';
        return $conexion->query($sql)->fetchAll();
    }
}
