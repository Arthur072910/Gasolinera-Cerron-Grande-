<?php
/**
 * Modelo: Proveedor
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Proveedor
{
    public $id_proveedor;
    public $nombre_empresa;
    public $registro_fiscal;
    public $telefono;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerTodos(PDO $conexion): array
    {
        $sql = 'SELECT id_proveedor AS id, nombre_empresa AS nombre FROM proveedores ORDER BY nombre_empresa';
        return $conexion->query($sql)->fetchAll();
    }

    public static function crear(PDO $conexion, string $nombre, string $registroFiscal, ?string $telefono): int
    {
        $stmt = $conexion->prepare(
            'INSERT INTO proveedores (nombre_empresa, registro_fiscal, telefono) VALUES (:nombre, :registro, :telefono)'
        );
        $stmt->execute([':nombre' => $nombre, ':registro' => $registroFiscal, ':telefono' => $telefono ?: null]);
        return (int) $conexion->lastInsertId();
    }
}
