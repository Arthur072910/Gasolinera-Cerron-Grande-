<?php
/**
 * Modelo: Usuario
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Usuario
{
    public $id_usuario;
    public $id_rol;
    public $nombre;
    public $pin_hash;
    public $estado;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerTodosConRol(PDO $conexion): array
    {
        $sql = 'SELECT u.id_usuario, u.id_rol, u.nombre, u.pin_hash, u.estado, r.nombre_rol
                FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
                ORDER BY u.id_usuario';
        return $conexion->query($sql)->fetchAll();
    }

    public static function obtenerActivosConRol(PDO $conexion): array
    {
        $sql = "SELECT u.id_usuario, u.id_rol, u.nombre, u.pin_hash, r.nombre_rol
                FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.estado = 'activo'";
        return $conexion->query($sql)->fetchAll();
    }

    public static function crear(PDO $conexion, int $idRol, string $nombre, string $pin): int
    {
        $stmt = $conexion->prepare('INSERT INTO usuarios (id_rol, nombre, pin_hash) VALUES (:id_rol, :nombre, :pin_hash)');
        $stmt->execute([
            ':id_rol'   => $idRol,
            ':nombre'   => $nombre,
            ':pin_hash' => password_hash($pin, PASSWORD_BCRYPT),
        ]);
        return (int) $conexion->lastInsertId();
    }

    public static function cambiarEstado(PDO $conexion, int $idUsuario, string $estado): void
    {
        $stmt = $conexion->prepare('UPDATE usuarios SET estado = :estado WHERE id_usuario = :id');
        $stmt->execute([':estado' => $estado, ':id' => $idUsuario]);
    }

    public static function obtenerPorId(PDO $conexion, int $idUsuario): ?array
    {
        $stmt = $conexion->prepare('SELECT id_usuario, id_rol, nombre, estado FROM usuarios WHERE id_usuario = :id');
        $stmt->execute([':id' => $idUsuario]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function actualizar(PDO $conexion, int $idUsuario, int $idRol, string $nombre): void
    {
        $stmt = $conexion->prepare('UPDATE usuarios SET id_rol = :id_rol, nombre = :nombre WHERE id_usuario = :id');
        $stmt->execute([':id_rol' => $idRol, ':nombre' => $nombre, ':id' => $idUsuario]);
    }

    public static function actualizarPin(PDO $conexion, int $idUsuario, string $pin): void
    {
        $stmt = $conexion->prepare('UPDATE usuarios SET pin_hash = :pin_hash WHERE id_usuario = :id');
        $stmt->execute([':pin_hash' => password_hash($pin, PASSWORD_BCRYPT), ':id' => $idUsuario]);
    }
}
