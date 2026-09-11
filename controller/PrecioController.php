<?php
/**
 * Controlador: Precios y mapeo dinamico de bombas
 * Conectado a `precios_combustible`. Registrar un precio nuevo cierra
 * automaticamente el que estaba vigente (fecha_fin_vigencia), dejando
 * el historial completo para auditoria.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/PrecioCombustible.php';

class PrecioController
{
    public static function precios(): array
    {
        $filas = PrecioCombustible::obtenerVigentes(Database::obtenerConexion());
        return array_map(fn ($f) => [
            'combustible'   => ucfirst($f['tipo_combustible']),
            'precio'        => (float) $f['precio_por_galon'],
            'vigente_desde' => $f['fecha_inicio_vigencia'],
        ], $filas);
    }

    public static function historialPrecios(): array
    {
        $filas = PrecioCombustible::obtenerHistorial(Database::obtenerConexion());
        return array_map(fn ($f) => [
            'combustible' => ucfirst($f['tipo_combustible']),
            'precio'      => (float) $f['precio_por_galon'],
            'desde'       => $f['fecha_inicio_vigencia'],
            'hasta'       => $f['fecha_fin_vigencia'] ?? '--',
            'usuario'     => $f['usuario'],
        ], $filas);
    }

    public static function registrarNuevoPrecio(string $combustible, float $precio, int $idUsuario): void
    {
        PrecioCombustible::registrarNuevo(Database::obtenerConexion(), strtolower($combustible), $precio, $idUsuario);
    }
}
