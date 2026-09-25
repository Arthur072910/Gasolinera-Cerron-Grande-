<?php
/**
 * Controlador: Usuarios y roles
 * Conectado a `usuarios` y `roles`.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../model/Usuario.php';
require_once __DIR__ . '/../model/Rol.php';

class UsuarioController
{
    public static function usuarios(): array
    {
        return Usuario::obtenerTodosConRol(Database::obtenerConexion());
    }

    public static function roles(): array
    {
        return Rol::obtenerTodos(Database::obtenerConexion());
    }

    public static function registrarUsuario(int $idRol, string $nombre, string $pin): void
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('El nombre es obligatorio.');
        }
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            throw new RuntimeException('El PIN debe tener entre 4 y 6 digitos numericos.');
        }
        Usuario::crear(Database::obtenerConexion(), $idRol, $nombre, $pin);
    }

    /**
     * $pin puede venir null (no se toco el campo en el formulario, se
     * conserva el PIN actual) o una cadena vacia/valida (se actualiza).
     */
    public static function editarUsuario(int $idUsuario, int $idRol, string $nombre, ?string $pin): void
    {
        $nombre = trim($nombre);
        if ($idUsuario <= 0) {
            throw new RuntimeException('Usuario invalido.');
        }
        if ($nombre === '') {
            throw new RuntimeException('El nombre es obligatorio.');
        }

        $conexion = Database::obtenerConexion();
        Usuario::actualizar($conexion, $idUsuario, $idRol, $nombre);

        if ($pin !== null && $pin !== '') {
            if (!preg_match('/^\d{4,6}$/', $pin)) {
                throw new RuntimeException('El PIN debe tener entre 4 y 6 digitos numericos.');
            }
            Usuario::actualizarPin($conexion, $idUsuario, $pin);
        }
    }

    public static function cambiarEstado(int $idUsuario, string $estado): void
    {
        if ($idUsuario === Sesion::idUsuarioActual()) {
            throw new RuntimeException('No puedes cambiar el estado de tu propio usuario.');
        }
        Usuario::cambiarEstado(Database::obtenerConexion(), $idUsuario, $estado);
    }
}
