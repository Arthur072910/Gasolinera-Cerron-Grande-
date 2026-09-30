<?php
/**
 * Modelo: Respaldo
 * A diferencia del resto de modelos, esto NO es una tabla de la base de
 * datos: gestiona los archivos .sql de respaldo que viven en disco
 * (ver RUTA_RESPALDOS, config/config.php). Cada respaldo es el volcado
 * completo de `gasolinera_cerron_grande` generado con mysqldump.
 */

class Respaldo
{
    public static function asegurarCarpeta(): void
    {
        if (!is_dir(RUTA_RESPALDOS)) {
            mkdir(RUTA_RESPALDOS, 0775, true);
        }
    }

    /** Mas reciente primero (el nombre de archivo ya lleva fecha/hora ordenable). */
    public static function listar(): array
    {
        self::asegurarCarpeta();
        $archivos = glob(RUTA_RESPALDOS . '/*.sql') ?: [];
        rsort($archivos);

        return array_map(fn ($ruta) => [
            'nombre' => basename($ruta),
            'tamano' => filesize($ruta),
            'fecha'  => filemtime($ruta),
        ], $archivos);
    }

    /**
     * Valida que $nombreArchivo sea un nombre de archivo simple (sin
     * rutas ni "..") que realmente exista dentro de RUTA_RESPALDOS, para
     * que nadie pueda pedir descargar/restaurar/borrar un archivo fuera
     * de esa carpeta (path traversal).
     */
    public static function rutaSegura(string $nombreArchivo): ?string
    {
        $nombreArchivo = basename($nombreArchivo);
        if (!preg_match('/^[A-Za-z0-9_\-\.]+\.sql$/', $nombreArchivo)) {
            return null;
        }
        $ruta = RUTA_RESPALDOS . '/' . $nombreArchivo;
        return is_file($ruta) ? $ruta : null;
    }

    public static function eliminar(string $nombreArchivo): void
    {
        $ruta = self::rutaSegura($nombreArchivo);
        if ($ruta === null) {
            throw new RuntimeException('Ese archivo de respaldo no existe.');
        }
        unlink($ruta);
    }
}
