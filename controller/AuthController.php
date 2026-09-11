<?php
/**
 * Controlador: Autenticacion
 * Valida el PIN contra `usuarios.pin_hash` (bcrypt) y, si coincide,
 * abre automaticamente el registro de asistencia (entrada) del usuario.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Usuario.php';
require_once __DIR__ . '/../model/Asistencia.php';

class AuthController
{
    public static function intentarIngreso(string $pin): bool
    {
        $conexion = Database::obtenerConexion();

        foreach (Usuario::obtenerActivosConRol($conexion) as $usuario) {
            if (password_verify($pin, $usuario['pin_hash'])) {
                $idAsistencia = Asistencia::registrarEntrada($conexion, (int) $usuario['id_usuario']);
                Sesion::iniciar(
                    (int) $usuario['id_usuario'],
                    $usuario['nombre'],
                    strtolower($usuario['nombre_rol']),
                    $idAsistencia
                );
                return true;
            }
        }
        return false;
    }

    public static function cerrarSesion(): void
    {
        $idAsistencia = Sesion::idAsistenciaActual();
        if ($idAsistencia !== null) {
            Asistencia::registrarSalida(Database::obtenerConexion(), $idAsistencia);
        }
        Sesion::cerrar();
    }
}
