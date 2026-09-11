<?php
/**
 * Modelo: LecturaTanque
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class LecturaTanque
{
    public $id_lectura_tanque;
    public $id_tanque;
    public $nivel_cm;
    public $galones_calculados;
    public $fecha_hora;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    /**
     * Punto donde el script puente Python (Arduino + sensor HC-SR04)
     * insertara cada lectura del nivel del tanque. Mientras tanto, el
     * panel de administracion permite registrarla manualmente.
     */
    public static function registrar(PDO $conexion, int $idTanque, float $nivelCm, float $galones): void
    {
        $stmt = $conexion->prepare(
            'INSERT INTO lecturas_tanque (id_tanque, nivel_cm, galones_calculados) VALUES (:id_tanque, :nivel_cm, :galones)'
        );
        $stmt->execute([':id_tanque' => $idTanque, ':nivel_cm' => $nivelCm, ':galones' => $galones]);
    }

    public static function obtenerHistorial(PDO $conexion, int $limite = 20): array
    {
        $sql = "SELECT lt.fecha_hora, t.nombre_tanque, t.tipo_combustible, lt.nivel_cm, lt.galones_calculados
                FROM lecturas_tanque lt
                JOIN tanques t ON t.id_tanque = lt.id_tanque
                ORDER BY lt.fecha_hora DESC, lt.id_lectura_tanque DESC
                LIMIT " . max(1, $limite);
        return $conexion->query($sql)->fetchAll();
    }
}
