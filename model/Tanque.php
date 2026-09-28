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
     * Fragmento SQL reutilizable: nivel EN VIVO de un tanque, partiendo
     * de su ultima lectura conocida (sensor/manual) y ajustandola con
     * todo lo despachado (resta) y recibido en cisterna (suma) desde
     * esa lectura. Asi el monitor de tanques y las validaciones de
     * despacho/recepcion reflejan la realidad aunque el Arduino todavia
     * no mande lecturas automaticas cada pocos segundos.
     */
    private const SQL_NIVEL_CALCULADO = "
        COALESCE(lt.galones_calculados, 0)
        - COALESCE((
            SELECT SUM(dvc.galones_despachados)
            FROM detalle_ventas_combustible dvc
            JOIN mangueras m2 ON m2.id_manguera = dvc.id_manguera
            JOIN ventas v ON v.id_venta = dvc.id_venta
            WHERE m2.id_tanque = t.id_tanque
              AND v.fecha_hora > COALESCE(lt.fecha_hora, '1970-01-01 00:00:00')
          ), 0)
        + COALESCE((
            SELECT SUM(rc.galones_medidos_tanque)
            FROM recepcion_cisternas rc
            WHERE rc.id_tanque = t.id_tanque
              AND rc.fecha_hora > COALESCE(lt.fecha_hora, '1970-01-01 00:00:00')
          ), 0)";

    private const SQL_JOIN_ULTIMA_LECTURA = "
        LEFT JOIN lecturas_tanque lt ON lt.id_lectura_tanque = (
            SELECT lt2.id_lectura_tanque FROM lecturas_tanque lt2
            WHERE lt2.id_tanque = t.id_tanque
            ORDER BY lt2.fecha_hora DESC, lt2.id_lectura_tanque DESC LIMIT 1
        )";

    /**
     * Tanques con su nivel EN VIVO (ver SQL_NIVEL_CALCULADO): no se
     * queda congelado en la ultima lectura, descuenta lo despachado y
     * suma lo recibido en cisterna desde entonces.
     */
    public static function obtenerTodosConNivel(PDO $conexion): array
    {
        $sql = "SELECT t.id_tanque, t.nombre_tanque, t.tipo_combustible, t.capacidad_galones, t.nivel_minimo_alerta,
                       " . self::SQL_NIVEL_CALCULADO . " AS nivel_actual
                FROM tanques t
                " . self::SQL_JOIN_ULTIMA_LECTURA . "
                ORDER BY t.id_tanque";
        return $conexion->query($sql)->fetchAll();
    }

    public static function obtenerTodos(PDO $conexion): array
    {
        return $conexion->query('SELECT id_tanque, nombre_tanque, tipo_combustible FROM tanques ORDER BY id_tanque')->fetchAll();
    }

    /**
     * Nivel en vivo y capacidad de UN tanque especifico. Se usa para
     * validar que un despacho no saque mas combustible del que hay
     * realmente, y que una recepcion no exceda la capacidad libre.
     */
    public static function obtenerNivelCalculado(PDO $conexion, int $idTanque): array
    {
        $sql = "SELECT t.capacidad_galones,
                       " . self::SQL_NIVEL_CALCULADO . " AS nivel_actual
                FROM tanques t
                " . self::SQL_JOIN_ULTIMA_LECTURA . "
                WHERE t.id_tanque = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id' => $idTanque]);
        $fila = $stmt->fetch();
        return [
            'nivel_actual' => $fila ? (float) $fila['nivel_actual'] : 0.0,
            'capacidad'    => $fila ? (float) $fila['capacidad_galones'] : 0.0,
        ];
    }
}
