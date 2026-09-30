<?php
/**
 * Manejo de sesion y control de acceso por rol.
 * La sesion se llena en AuthController tras validar el PIN contra
 * `usuarios.pin_hash` en la base de datos.
 */

if (session_status() === PHP_SESSION_NONE) {
    // La cookie de sesion se marca "Secure" solo si la peticion ya viene
    // por HTTPS: hoy el sistema corre sobre HTTP en WAMP/localhost, y
    // forzar Secure ahi haria que el navegador simplemente descarte la
    // cookie (nunca se podria iniciar sesion). El dia que esto quede
    // detras de HTTPS real, empieza a aplicar solo sin tocar este codigo.
    $porHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $porHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
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

    /**
     * Guarda el recibo de la ultima venta cobrada para que la pantalla de
     * POS lo pueda mostrar (y permitir imprimirlo) justo despues del
     * redirect que sigue a index.php?accion=venta_tienda.
     */
    public static function guardarUltimoTicket(array $datos): void
    {
        $_SESSION['ultimo_ticket'] = $datos;
    }

    public static function leerUltimoTicket(): ?array
    {
        if (!isset($_SESSION['ultimo_ticket'])) {
            return null;
        }
        $datos = $_SESSION['ultimo_ticket'];
        unset($_SESSION['ultimo_ticket']);
        return $datos;
    }

    /**
     * Bloqueo temporal por intentos fallidos de PIN (fuerza bruta). Se
     * lleva por sesion de navegador: cada terminal/pestana tiene su
     * propio contador, igual que un lector de tarjetas fisico.
     */
    private const LOGIN_MAX_INTENTOS      = 5;
    private const LOGIN_BLOQUEO_SEGUNDOS  = 120;

    public static function registrarIntentoFallido(): void
    {
        $_SESSION['login_intentos'] = self::obtenerIntentosFallidos() + 1;
        if ($_SESSION['login_intentos'] >= self::LOGIN_MAX_INTENTOS) {
            $_SESSION['login_bloqueado_hasta'] = time() + self::LOGIN_BLOQUEO_SEGUNDOS;
        }
    }

    public static function obtenerIntentosFallidos(): int
    {
        return (int) ($_SESSION['login_intentos'] ?? 0);
    }

    public static function intentosRestantes(): int
    {
        return max(0, self::LOGIN_MAX_INTENTOS - self::obtenerIntentosFallidos());
    }

    public static function segundosDeBloqueoRestantes(): int
    {
        $hasta = $_SESSION['login_bloqueado_hasta'] ?? null;
        if ($hasta === null) {
            return 0;
        }
        $restante = $hasta - time();
        if ($restante <= 0) {
            unset($_SESSION['login_bloqueado_hasta'], $_SESSION['login_intentos']);
            return 0;
        }
        return $restante;
    }

    public static function reiniciarIntentosFallidos(): void
    {
        unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
    }

    /**
     * Proteccion CSRF: un codigo secreto por sesion (no por formulario,
     * para no romper si el usuario tiene dos pestanas abiertas) que cada
     * formulario POST manda de vuelta como campo oculto. Sin el token
     * correcto, index.php rechaza la accion. Evita que una pagina
     * maliciosa en otra pestana haga que el navegador envie una accion
     * aqui aprovechando que ya hay sesion iniciada.
     */
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validarCsrf(?string $token): bool
    {
        return $token !== null
            && $token !== ''
            && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
