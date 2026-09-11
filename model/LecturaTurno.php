<?php
/**
 * Modelo: LecturaTurno
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class LecturaTurno
{
    public $id_lectura;
    public $id_turno;
    public $id_manguera;
    public $contador_inicial;
    public $contador_final;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    /**
     * Al abrir un turno de pista se toma "fotografia" del contador
     * acumulado de cada manguera activa (equivalente a anotar el
     * totalizador mecanico al iniciar el turno).
     */
    public static function abrirParaTurno(PDO $conexion, int $idTurno, int $idManguera, float $contadorInicial): void
    {
        $stmt = $conexion->prepare(
            'INSERT INTO lecturas_turno (id_turno, id_manguera, contador_inicial) VALUES (:id_turno, :id_manguera, :contador_inicial)'
        );
        $stmt->execute([':id_turno' => $idTurno, ':id_manguera' => $idManguera, ':contador_inicial' => $contadorInicial]);
    }

    /**
     * Al cerrar el turno se toma la segunda fotografia (contador_final)
     * de cada manguera que tenia lectura abierta en ese turno.
     */
    public static function cerrarTodasDelTurno(PDO $conexion, int $idTurno): void
    {
        $stmt = $conexion->prepare(
            'UPDATE lecturas_turno lt
             JOIN mangueras m ON m.id_manguera = lt.id_manguera
             SET lt.contador_final = m.contador_acumulado
             WHERE lt.id_turno = :id_turno AND lt.contador_final IS NULL'
        );
        $stmt->execute([':id_turno' => $idTurno]);
    }

    /**
     * "contador_actual" es en vivo mientras el turno sigue abierto
     * (COALESCE con el acumulado real de la manguera); una vez cerrado
     * el turno, coincide con contador_final.
     */
    public static function obtenerResumenTurno(PDO $conexion, int $idTurno): array
    {
        $sql = "SELECT m.color_identificador, t.tipo_combustible, lt.contador_inicial, lt.contador_final,
                       COALESCE(lt.contador_final, m.contador_acumulado) AS contador_actual
                FROM lecturas_turno lt
                JOIN mangueras m ON m.id_manguera = lt.id_manguera
                JOIN tanques t ON t.id_tanque = m.id_tanque
                WHERE lt.id_turno = :id_turno";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id_turno' => $idTurno]);
        return $stmt->fetchAll();
    }
}
