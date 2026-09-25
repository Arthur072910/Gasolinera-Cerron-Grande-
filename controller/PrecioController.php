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
    /**
     * Ademas del precio vigente, calcula la variacion contra el precio
     * inmediatamente anterior de ese mismo combustible (si existe), para
     * mostrar la tendencia (subio/bajo) en el panel.
     */
    public static function precios(): array
    {
        $conexion = Database::obtenerConexion();
        $vigentes = PrecioCombustible::obtenerVigentes($conexion);
        $historial = PrecioCombustible::obtenerHistorial($conexion);

        $resultado = [];
        foreach ($vigentes as $f) {
            $tipo     = $f['tipo_combustible'];
            $actual   = (float) $f['precio_por_galon'];
            $anterior = null;

            foreach ($historial as $h) {
                if ($h['tipo_combustible'] === $tipo && $h['fecha_fin_vigencia'] !== null) {
                    $anterior = (float) $h['precio_por_galon'];
                    break;
                }
            }

            $resultado[] = [
                'combustible'   => ucfirst($tipo),
                'precio'        => $actual,
                'vigente_desde' => $f['fecha_inicio_vigencia'],
                'delta'         => $anterior !== null ? round($actual - $anterior, 3) : null,
            ];
        }
        return $resultado;
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
