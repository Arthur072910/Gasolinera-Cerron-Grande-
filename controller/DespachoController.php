<?php
/**
 * Controlador: Despacho de combustible (POS Pista)
 * Flujo real de 4 pasos:
 *   1) Elegir bomba  -> 2) Elegir manguera de esa bomba
 *   3) Elegir modalidad de venta -> 4) Ingresar monto/litros y confirmar
 * Al confirmar, registra la venta contra `ventas` / `detalle_ventas_combustible`
 * y actualiza el contador acumulado de la manguera (simula el totalizador).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Bomba.php';
require_once __DIR__ . '/../model/Manguera.php';
require_once __DIR__ . '/../model/PrecioCombustible.php';
require_once __DIR__ . '/../model/Venta.php';
require_once __DIR__ . '/../model/DetalleVentaCombustible.php';

class DespachoController
{
    public static function bombas(): array
    {
        return Bomba::obtenerTodasConMangueras(Database::obtenerConexion());
    }

    public static function modalidadesVenta(): array
    {
        return [
            ['id' => 'monto',  'nombre' => 'Monto fijo',   'ayuda' => 'El cliente indica cuanto dinero quiere cargar.'],
            ['id' => 'litros', 'nombre' => 'Litros fijos', 'ayuda' => 'El cliente indica cuantos galones quiere.'],
            ['id' => 'lleno',  'nombre' => 'Tanque lleno', 'ayuda' => 'Se despacha hasta que el cliente indique parar.'],
        ];
    }

    public static function precioVigente(): array
    {
        return PrecioCombustible::obtenerVigentesPorTipo(Database::obtenerConexion());
    }

    /**
     * Registra el despacho: calcula galones/monto segun la modalidad
     * usando SIEMPRE el precio vigente leido del servidor (nunca el que
     * pudiera llegar del formulario), y deja todo dentro de una
     * transaccion para no descuadrar ventas con el contador de la manguera.
     */
    public static function procesarDespacho(int $idManguera, string $modalidad, float $valorEntrada, string $metodoPago, int $idTurno): array
    {
        $conexion = Database::obtenerConexion();
        $manguera = Manguera::obtenerParaDespacho($conexion, $idManguera);

        if ($manguera === null || $manguera['estado_bomba'] !== 'activa') {
            throw new RuntimeException('La manguera seleccionada no esta disponible.');
        }

        $precio = (float) $manguera['precio_por_galon'];

        if ($modalidad === 'litros' || $modalidad === 'lleno') {
            $galones = $valorEntrada;
            $monto   = round($galones * $precio, 2);
        } else {
            $monto   = $valorEntrada;
            $galones = $precio > 0 ? round($monto / $precio, 3) : 0.0;
        }

        if ($galones <= 0 || $monto <= 0) {
            throw new RuntimeException('El monto o los galones deben ser mayores a cero.');
        }

        $conexion->beginTransaction();
        try {
            $idVenta = Venta::crear($conexion, $idTurno, 'ticket', $metodoPago, $monto);
            DetalleVentaCombustible::crear($conexion, $idVenta, $idManguera, $galones, $precio, $monto);
            Manguera::incrementarContador($conexion, $idManguera, $galones);
            $conexion->commit();
        } catch (Exception $e) {
            $conexion->rollBack();
            throw $e;
        }

        return [
            'id_venta'    => $idVenta,
            'combustible' => ucfirst($manguera['tipo_combustible']),
            'galones'     => $galones,
            'precio'      => $precio,
            'total'       => $monto,
        ];
    }
}
