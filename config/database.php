<?php
/**
 * Conexion a la base de datos MySQL (phpMyAdmin / WAMP).
 *
 * Importa primero `database/gasolinera_cerron_grande.sql` desde
 * phpMyAdmin (pestana SQL) antes de usar el sistema con datos reales.
 *
 * Uso:
 *   require_once __DIR__ . '/database.php';
 *   $conexion = Database::obtenerConexion();
 */

class Database
{
    private static ?PDO $conexion = null;

    private const HOST    = '127.0.0.1';
    private const PUERTO  = '3306';
    private const NOMBRE  = 'gasolinera_cerron_grande';
    private const USUARIO = 'root';
    private const CLAVE   = '';
    private const CHARSET = 'utf8mb4';

    /** Para RespaldoController: arma los comandos de mysqldump/mysql sin duplicar credenciales en otro archivo. */
    public static function datosConexion(): array
    {
        return [
            'host'    => self::HOST,
            'puerto'  => self::PUERTO,
            'nombre'  => self::NOMBRE,
            'usuario' => self::USUARIO,
            'clave'   => self::CLAVE,
        ];
    }

    public static function obtenerConexion(): PDO
    {
        if (self::$conexion === null) {
            $dsn = 'mysql:host=' . self::HOST . ';port=' . self::PUERTO
                 . ';dbname=' . self::NOMBRE . ';charset=' . self::CHARSET;
            self::$conexion = new PDO($dsn, self::USUARIO, self::CLAVE, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$conexion;
    }
}
