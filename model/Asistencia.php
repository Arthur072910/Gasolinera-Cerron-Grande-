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
