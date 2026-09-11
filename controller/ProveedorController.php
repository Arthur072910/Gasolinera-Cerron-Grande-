<?php
/**
 * Controlador: Proveedores y recepcion de cisternas
 * Conectado a `proveedores` y `recepcion_cisternas`.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Proveedor.php';
require_once __DIR__ . '/../model/RecepcionCisterna.php';

class ProveedorController
{
    public static function proveedores(): array
    {
        return Proveedor::obtenerTodos(Database::obtenerConexion());
    }

    public static function recepciones(): array
    {
        return RecepcionCisterna::obtenerTodasConDetalle(Database::obtenerConexion());
    }

    public static function registrarProveedor(string $nombre, string $registroFiscal, ?string $telefono): void
    {
        Proveedor::crear(Database::obtenerConexion(), $nombre, $registroFiscal, $telefono);
    }

    public static function registrarRecepcion(
        int $idProveedor,
        int $idTanque,
        string $numeroFactura,
        float $galonesFacturados,
        float $galonesMedidos,
        float $costoTotal
    ): void {
        RecepcionCisterna::crear(Database::obtenerConexion(), $idProveedor, $idTanque, $numeroFactura, $galonesFacturados, $galonesMedidos, $costoTotal);
    }
}
