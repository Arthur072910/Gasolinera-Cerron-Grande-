<?php
/**
 * Modelo: Categoria
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Categoria
{
    public $id_categoria;
    public $nombre_categoria;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerTodas(PDO $conexion): array
    {
        $sql = 'SELECT id_categoria AS id, nombre_categoria AS nombre FROM categorias ORDER BY nombre_categoria';
        return $conexion->query($sql)->fetchAll();
    }

    public static function crear(PDO $conexion, string $nombre): int
    {
        $stmt = $conexion->prepare('INSERT INTO categorias (nombre_categoria) VALUES (:nombre)');
        $stmt->execute([':nombre' => $nombre]);
        return (int) $conexion->lastInsertId();
    }
}
