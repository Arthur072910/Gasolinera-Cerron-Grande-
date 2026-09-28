<?php
/**
 * Controlador: Proveedores y recepcion de cisternas
 * Conectado a `proveedores` y `recepcion_cisternas`.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Proveedor.php';
require_once __DIR__ . '/../model/RecepcionCisterna.php';
require_once __DIR__ . '/../model/Tanque.php';
require_once __DIR__ . '/../model/LecturaTanque.php';

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
        self::validarProveedor($nombre, $registroFiscal);
        $conexion = Database::obtenerConexion();
        try {
            Proveedor::crear($conexion, trim($nombre), trim($registroFiscal), $telefono !== null ? trim($telefono) : null);
        } catch (PDOException $e) {
            throw self::traducirErrorUnico($e);
        }
    }

    public static function editarProveedor(int $idProveedor, string $nombre, string $registroFiscal, ?string $telefono): void
    {
        if ($idProveedor <= 0) {
            throw new RuntimeException('Proveedor invalido.');
        }
        self::validarProveedor($nombre, $registroFiscal);
        $conexion = Database::obtenerConexion();
        try {
            Proveedor::actualizar($conexion, $idProveedor, trim($nombre), trim($registroFiscal), $telefono !== null ? trim($telefono) : null);
        } catch (PDOException $e) {
            throw self::traducirErrorUnico($e);
        }
    }

    public static function eliminarProveedor(int $idProveedor): void
    {
        try {
            Proveedor::eliminar(Database::obtenerConexion(), $idProveedor);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                throw new RuntimeException('No se puede eliminar: este proveedor ya tiene recepciones de cisterna registradas.');
            }
            throw $e;
        }
    }

    /**
     * $nivelCmTrasDescarga es opcional: si se envia, ademas de registrar
     * la recepcion se registra una nueva lectura del tanque destino
     * (nivel actual + galones medidos en la descarga), igual que si se
     * hubiera tomado manualmente desde el panel de Tanques. Si se deja
     * en blanco, la recepcion queda registrada pero el nivel del tanque
     * no se actualiza automaticamente.
     */
    public static function registrarRecepcion(
        int $idProveedor,
        int $idTanque,
        string $numeroFactura,
        float $galonesFacturados,
        float $galonesMedidos,
        float $costoTotal,
        ?float $nivelCmTrasDescarga = null
    ): void {
        if (trim($numeroFactura) === '') {
            throw new RuntimeException('El numero de factura es obligatorio.');
        }
        if ($galonesFacturados <= 0 || $galonesMedidos <= 0) {
            throw new RuntimeException('Los galones facturados y medidos deben ser mayores a cero.');
        }
        if ($costoTotal <= 0) {
            throw new RuntimeException('El costo total debe ser mayor a cero.');
        }

        $conexion = Database::obtenerConexion();
        $tanque   = Tanque::obtenerNivelCalculado($conexion, $idTanque);
        $espacioLibre = $tanque['capacidad'] - $tanque['nivel_actual'];

        if ($galonesMedidos > $espacioLibre) {
            throw new RuntimeException(sprintf(
                'Esa cantidad excede la capacidad libre del tanque (espacio disponible: %.1f gal).',
                max(0, $espacioLibre)
            ));
        }

        RecepcionCisterna::crear($conexion, $idProveedor, $idTanque, trim($numeroFactura), $galonesFacturados, $galonesMedidos, $costoTotal);

        if ($nivelCmTrasDescarga !== null) {
            LecturaTanque::registrar($conexion, $idTanque, $nivelCmTrasDescarga, $tanque['nivel_actual'] + $galonesMedidos);
        }
    }

    private static function validarProveedor(string $nombre, string $registroFiscal): void
    {
        if (trim($nombre) === '') {
            throw new RuntimeException('El nombre de la empresa es obligatorio.');
        }
        if (trim($registroFiscal) === '') {
            throw new RuntimeException('El registro fiscal (NRC/NIT) es obligatorio.');
        }
    }

    private static function traducirErrorUnico(PDOException $e): RuntimeException
    {
        if ((int) $e->getCode() === 23000) {
            return new RuntimeException('Ya existe un proveedor registrado con ese registro fiscal.');
        }
        return new RuntimeException('Ocurrio un error al guardar el proveedor.');
    }
}
