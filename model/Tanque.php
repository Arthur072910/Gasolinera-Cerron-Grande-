<?php
/**
 * Modelo: Tanque
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Tanque
{
    public $id_tanque;
    public $nombre_tanque;
    public $tipo_combustible;
    public $capacidad_galones;
    public $nivel_minimo_alerta;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    /**
     * Tanques con su nivel mas reciente conocido (ultima fila de
     * `lecturas_tanque`). Si nunca se ha registrado una lectura, el
     * nivel actual se reporta como 0.
     */
    public static function obtenerTodosConNivel(PDO $conexion): array
    {
        $sql = "SELECT t.id_tanque, t.nombre_tanque, t.tipo_combustible, t.capacidad_galones, t.nivel_minimo_alerta,
                       COALESCE(lt.galones_calculados, 0) AS nivel_actual
                FROM tanques t
                LEFT JOIN lecturas_tanque lt ON lt.id_lectura_tanque = (
                    SELECT lt2.id_lectura_tanque FROM lecturas_tanque lt2
                    WHERE lt2.id_tanque = t.id_tanque
                    ORDER BY lt2.fecha_hora DESC, lt2.id_lectura_tanque DESC LIMIT 1
                )
                ORDER BY t.id_tanque";
        return $conexion->query($sql)->fetchAll();
    }

    public static function obtenerTodos(PDO $conexion): array
    {
        return $conexion->query('SELECT id_tanque, nombre_tanque, tipo_combustible FROM tanques ORDER BY id_tanque')->fetchAll();
    }
}
