<?php
/**
 * Modelo: Rol
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Rol
{
    public $id_rol;
    public $nombre_rol;
    public $descripcion;

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
        return $conexion->query('SELECT id_rol, nombre_rol FROM roles ORDER BY id_rol')->fetchAll();
    }
}
