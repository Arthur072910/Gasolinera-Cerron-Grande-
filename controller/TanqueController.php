<?php
/**
 * Controlador: Monitoreo de Tanques (integracion Arduino)
 * Lee el nivel real desde `lecturas_tanque`. Mientras no este conectado
 * el sensor HC-SR04 + script puente Python, el panel de administracion
 * permite registrar una lectura manual (simulacion) desde esta misma
 * tabla, dejando el enganche futuro listo sin cambiar el modelo.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Tanque.php';
require_once __DIR__ . '/../model/LecturaTanque.php';

class TanqueController
{
    public static function estadoTanques(): array
    {
        $conexion  = Database::obtenerConexion();
        $resultado = [];

        foreach (Tanque::obtenerTodosConNivel($conexion) as $t) {
            $nivelActual = (float) $t['nivel_actual'];
            $nivelMinimo = (float) $t['nivel_minimo_alerta'];

            if ($nivelActual <= $nivelMinimo) {
                $estadoLed = 'rojo';
            } elseif ($nivelActual <= $nivelMinimo * 1.5) {
                $estadoLed = 'amarillo';
            } else {
                $estadoLed = 'verde';
            }

            $resultado[] = [
                'id'           => $t['id_tanque'],
                'nombre'       => $t['nombre_tanque'],
                'combustible'  => ucfirst($t['tipo_combustible']),
                'capacidad'    => (float) $t['capacidad_galones'],
                'nivel_actual' => $nivelActual,
                'nivel_minimo' => $nivelMinimo,
                'estado_led'   => $estadoLed,
            ];
        }
        return $resultado;
    }

    public static function historialLecturas(int $limite = 20): array
    {
        $filas = LecturaTanque::obtenerHistorial(Database::obtenerConexion(), $limite);
        return array_map(fn ($f) => [
            'fecha'    => $f['fecha_hora'],
            'tanque'   => $f['nombre_tanque'] . ' - ' . ucfirst($f['tipo_combustible']),
            'nivel_cm' => (float) $f['nivel_cm'],
            'galones'  => (float) $f['galones_calculados'],
        ], $filas);
    }

    public static function registrarLecturaManual(int $idTanque, float $nivelCm, float $galones): void
    {
        LecturaTanque::registrar(Database::obtenerConexion(), $idTanque, $nivelCm, $galones);
    }
}
