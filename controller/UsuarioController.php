<?php
/**
 * Controlador: Usuarios y roles
 * Conectado a `usuarios` y `roles`.
 */
require_once __DIR__ . '/../config/database.php';
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
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            throw new RuntimeException('El PIN debe tener entre 4 y 6 digitos numericos.');
        }
        Usuario::crear(Database::obtenerConexion(), $idRol, $nombre, $pin);
    }

    public static function cambiarEstado(int $idUsuario, string $estado): void
    {
        Usuario::cambiarEstado(Database::obtenerConexion(), $idUsuario, $estado);
    }
}
