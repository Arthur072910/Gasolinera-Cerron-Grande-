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
    public static function registros(): array
    {
        $filas = Asistencia::obtenerTodasConUsuario(Database::obtenerConexion());
        return array_map(fn ($f) => [
            'usuario' => $f['usuario'],
            'rol'     => $f['rol'],
            'entrada' => $f['fecha_hora_entrada'],
            'salida'  => $f['fecha_hora_salida'] ?? '--',
        ], $filas);
    }
}
