<?php
/**
 * Controlador: Control de Asistencia Automatizado
 * Cada vez que un usuario inicia sesion con su PIN se guarda un
 * registro automatico en `asistencia` (ver AuthController). Aqui solo
 * se lee esa bitacora para mostrarla.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Asistencia.php';

class AsistenciaController
{
    /**
     * 'duracion' viene null cuando el usuario sigue en turno (sin salida
     * registrada todavia); en ese caso la vista muestra "En curso".
     */
    public static function registros(): array
    {
        $filas = Asistencia::obtenerTodasConUsuario(Database::obtenerConexion());
        return array_map(function ($f) {
            $salida = $f['fecha_hora_salida'];
            return [
                'usuario'  => $f['usuario'],
                'rol'      => $f['rol'],
                'entrada'  => $f['fecha_hora_entrada'],
                'salida'   => $salida ?? '--',
                'duracion' => $salida !== null
                    ? self::formatearDuracion(strtotime($salida) - strtotime($f['fecha_hora_entrada']))
                    : null,
            ];
        }, $filas);
    }

    private static function formatearDuracion(int $segundos): string
    {
        $segundos = max(0, $segundos);
        $horas    = intdiv($segundos, 3600);
        $minutos  = intdiv($segundos % 3600, 60);
        return sprintf('%dh %02dm', $horas, $minutos);
    }
}
