<?php
/**
 * Manejo de sesion y control de acceso por rol.
 * La sesion se llena en AuthController tras validar el PIN contra
 * `usuarios.pin_hash` en la base de datos.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Sesion
{
    public static function iniciar(int $idUsuario, string $nombreUsuario, string $rol, int $idAsistencia): void
    {
        $_SESSION['id_usuario']     = $idUsuario;
        $_SESSION['nombre_usuario'] = $nombreUsuario;
        $_SESSION['rol']            = $rol;
        $_SESSION['id_asistencia']  = $idAsistencia;
        $_SESSION['inicio']         = date('Y-m-d H:i:s');
    }

    public static function estaActiva(): bool
    {
        return isset($_SESSION['rol']) && in_array($_SESSION['rol'], ROLES_VALIDOS, true);
    }

    public static function rolActual(): ?string
    {
        return $_SESSION['rol'] ?? null;
    }

    public static function nombreActual(): string
    {
        return $_SESSION['nombre_usuario'] ?? 'Invitado';
    }

    public static function idUsuarioActual(): ?int
    {
        return $_SESSION['id_usuario'] ?? null;
    }

    public static function idAsistenciaActual(): ?int
    {
        return $_SESSION['id_asistencia'] ?? null;
    }

    public static function requerirRol(array $rolesPermitidos): void
    {
        if (!self::estaActiva() || !in_array(self::rolActual(), $rolesPermitidos, true)) {
            header('Location: index.php');
            exit;
        }
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function flash(string $tipo, string $mensaje): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    public static function leerFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Guarda los datos de un formulario para poder re-mostrarlos (p.ej.
     * reabrir un modal ya lleno) si la accion que se procesa a
     * continuacion termina en error. Se combina con flash()/leerFlash():
     * la vista decide usarlos solo cuando el flash resulto ser un error.
     */
    public static function flashDatos(array $datos): void
    {
        $_SESSION['flash_datos'] = $datos;
    }

    public static function leerFlashDatos(): ?array
    {
        if (!isset($_SESSION['flash_datos'])) {
            return null;
        }
        $datos = $_SESSION['flash_datos'];
        unset($_SESSION['flash_datos']);
        return $datos;
    }
}
