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
                $idAsistencia = self::abrirOResumirAsistencia($conexion, (int) $usuario['id_usuario']);
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

    /**
     * Si el usuario ya tenia una sesion de asistencia abierta de HOY
     * (p.ej. cerro la pestana sin dar "Cerrar sesion"), la reanuda en
     * vez de crear un registro duplicado. Cualquier otra sesion que
     * haya quedado abierta (duplicados de hoy, o abandonadas de dias
     * anteriores) se cierra automaticamente en este mismo momento, asi
     * que un usuario nunca queda con mas de una sesion "en curso".
     */
    private static function abrirOResumirAsistencia(PDO $conexion, int $idUsuario): int
    {
        $hoy         = date('Y-m-d');
        $idAResumir  = null;

        foreach (Asistencia::obtenerTodasAbiertasPorUsuario($conexion, $idUsuario) as $fila) {
            $fecha = substr($fila['fecha_hora_entrada'], 0, 10);

            if ($idAResumir === null && $fecha === $hoy) {
                $idAResumir = (int) $fila['id_asistencia'];
                continue;
            }

            $cierre = $fecha === $hoy ? date('Y-m-d H:i:s') : ($fecha . ' 23:59:59');
            Asistencia::cerrarConFecha($conexion, (int) $fila['id_asistencia'], $cierre);
        }

        return $idAResumir ?? Asistencia::registrarEntrada($conexion, $idUsuario);
    }
}
