<?php
/**
 * Controlador: Respaldos de la base de datos (solo Administrador)
 * Envuelve mysqldump/mysql (linea de comandos, ver config/config.php)
 * para crear, listar, restaurar y borrar respaldos .sql desde la vista
 * de administracion. Restaurar SIEMPRE crea antes un respaldo de
 * seguridad del estado actual, para poder deshacerlo si algo sale mal.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Respaldo.php';

class RespaldoController
{
    private const TAMANO_MAXIMO_SUBIDA = 50 * 1024 * 1024; // 50 MB

    public static function listar(): array
    {
        return array_map(fn ($r) => [
            'nombre'         => $r['nombre'],
            'tamano_legible' => self::formatearTamano($r['tamano']),
            'fecha'          => date('d/m/Y H:i:s', $r['fecha']),
        ], Respaldo::listar());
    }

    /** Vuelca la base completa a un .sql nuevo con mysqldump. Devuelve el nombre del archivo creado. */
    public static function crear(): string
    {
        Respaldo::asegurarCarpeta();
        $datos         = Database::datosConexion();
        $nombreArchivo = 'respaldo_' . date('Y-m-d_His') . '.sql';
        $rutaDestino   = RUTA_RESPALDOS . '/' . $nombreArchivo;

        $comando = sprintf(
            '%s --host=%s --port=%s --user=%s %s --routines --triggers %s > %s 2>&1',
            escapeshellarg(RUTA_MYSQLDUMP),
            escapeshellarg($datos['host']),
            escapeshellarg($datos['puerto']),
            escapeshellarg($datos['usuario']),
            $datos['clave'] !== '' ? '--password=' . escapeshellarg($datos['clave']) : '',
            escapeshellarg($datos['nombre']),
            escapeshellarg($rutaDestino)
        );

        exec($comando, $salida, $codigo);

        if ($codigo !== 0 || !is_file($rutaDestino) || filesize($rutaDestino) === 0) {
            if (is_file($rutaDestino)) {
                unlink($rutaDestino);
            }
            throw new RuntimeException('No se pudo generar el respaldo. Revisa que mysqldump este disponible en la ruta configurada.');
        }

        return $nombreArchivo;
    }

    /** Restaura un respaldo ya listado (por nombre de archivo). Devuelve el nombre del respaldo de seguridad previo. */
    public static function restaurar(string $nombreArchivo): string
    {
        $ruta = Respaldo::rutaSegura($nombreArchivo);
        if ($ruta === null) {
            throw new RuntimeException('Ese archivo de respaldo no existe.');
        }
        return self::restaurarDesdeRuta($ruta);
    }

    /** Recibe un .sql subido desde el navegador (un $_FILES[...]) y lo restaura directamente. */
    public static function subirYRestaurar(array $archivoSubido): string
    {
        if (!isset($archivoSubido['tmp_name'], $archivoSubido['error']) || $archivoSubido['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se recibio ningun archivo valido.');
        }
        if (!is_uploaded_file($archivoSubido['tmp_name'])) {
            throw new RuntimeException('El archivo recibido no es valido.');
        }
        if ($archivoSubido['size'] > self::TAMANO_MAXIMO_SUBIDA) {
            throw new RuntimeException('El archivo es demasiado grande (maximo 50 MB).');
        }
        if (!preg_match('/\.sql$/i', $archivoSubido['name'])) {
            throw new RuntimeException('El archivo debe tener extension .sql.');
        }

        Respaldo::asegurarCarpeta();
        $rutaTemporal = RUTA_RESPALDOS . '/subido_' . date('Y-m-d_His') . '.sql';
        if (!move_uploaded_file($archivoSubido['tmp_name'], $rutaTemporal)) {
            throw new RuntimeException('No se pudo guardar el archivo subido.');
        }

        return self::restaurarDesdeRuta($rutaTemporal);
    }

    private static function restaurarDesdeRuta(string $rutaArchivo): string
    {
        // Respaldo de seguridad automatico ANTES de restaurar: si el
        // archivo no era lo que se esperaba (o algo mas sale mal), se
        // puede volver al estado de justo antes sin haber perdido nada.
        $nombreSeguridad = self::crear();

        $datos   = Database::datosConexion();
        $comando = sprintf(
            '%s --host=%s --port=%s --user=%s %s %s < %s 2>&1',
            escapeshellarg(RUTA_MYSQL_CLI),
            escapeshellarg($datos['host']),
            escapeshellarg($datos['puerto']),
            escapeshellarg($datos['usuario']),
            $datos['clave'] !== '' ? '--password=' . escapeshellarg($datos['clave']) : '',
            escapeshellarg($datos['nombre']),
            escapeshellarg($rutaArchivo)
        );

        exec($comando, $salida, $codigo);

        if ($codigo !== 0) {
            throw new RuntimeException(
                'No se pudo restaurar el respaldo (se guardo un respaldo de seguridad antes de intentarlo: '
                . $nombreSeguridad . '). Detalle: ' . implode(' ', array_slice($salida, -3))
            );
        }

        return $nombreSeguridad;
    }

    public static function eliminar(string $nombreArchivo): void
    {
        Respaldo::eliminar($nombreArchivo);
    }

    public static function rutaParaDescargar(string $nombreArchivo): ?string
    {
        return Respaldo::rutaSegura($nombreArchivo);
    }

    private static function formatearTamano(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
