<?php
/**
 * Modelo: Bomba
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Bomba
{
    public $id_bomba;
    public $numero_bomba;
    public $estado;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    /**
     * Bombas con sus mangueras anidadas, listas para el asistente de
     * 4 pasos del despachador (bomba -> manguera -> modalidad -> cobro).
     * Una manguera se marca "bloqueada" si su bomba no esta activa.
     */
    public static function obtenerTodasConMangueras(PDO $conexion): array
    {
        $bombas = $conexion->query('SELECT id_bomba, numero_bomba, estado FROM bombas ORDER BY numero_bomba')->fetchAll();

        $sqlMangueras = $conexion->prepare(
            'SELECT m.id_manguera, m.color_identificador, t.tipo_combustible
             FROM mangueras m JOIN tanques t ON t.id_tanque = m.id_tanque
             WHERE m.id_bomba = :id_bomba ORDER BY m.id_manguera'
        );

        $resultado = [];
        foreach ($bombas as $b) {
            $sqlMangueras->execute([':id_bomba' => $b['id_bomba']]);
            $mangueras = [];
            foreach ($sqlMangueras->fetchAll() as $m) {
                $mangueras[] = [
                    'id'          => $m['id_manguera'],
                    'combustible' => ucfirst($m['tipo_combustible']),
                    'color'       => $m['color_identificador'],
                    'estado'      => $b['estado'] === 'activa' ? 'disponible' : 'bloqueada',
                ];
            }
            $resultado[] = [
                'id'        => $b['id_bomba'],
                'numero'    => $b['numero_bomba'],
                'estado'    => $b['estado'],
                'mangueras' => $mangueras,
            ];
        }
        return $resultado;
    }
}
