<?php
/**
 * Modelo: Manguera
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class Manguera
{
    public $id_manguera;
    public $id_bomba;
    public $id_tanque;
    public $color_identificador;
    public $contador_acumulado;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    /**
     * Datos de la manguera confirmados desde el servidor (nunca desde el
     * cliente): bomba, tipo de combustible y precio vigente aplicable.
     */
    public static function obtenerParaDespacho(PDO $conexion, int $idManguera): ?array
    {
        $sql = "SELECT m.id_manguera, m.id_bomba, m.contador_acumulado, t.tipo_combustible,
                       b.estado AS estado_bomba,
                       p.precio_por_galon
                FROM mangueras m
                JOIN tanques t ON t.id_tanque = m.id_tanque
                JOIN bombas b ON b.id_bomba = m.id_bomba
                JOIN precios_combustible p ON p.tipo_combustible = t.tipo_combustible AND p.fecha_fin_vigencia IS NULL
                WHERE m.id_manguera = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id' => $idManguera]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function incrementarContador(PDO $conexion, int $idManguera, float $galones): void
    {
        $stmt = $conexion->prepare(
            'UPDATE mangueras SET contador_acumulado = contador_acumulado + :galones WHERE id_manguera = :id'
        );
        $stmt->execute([':galones' => $galones, ':id' => $idManguera]);
    }

    public static function obtenerContadorActual(PDO $conexion, int $idManguera): float
    {
        $stmt = $conexion->prepare('SELECT contador_acumulado FROM mangueras WHERE id_manguera = :id');
        $stmt->execute([':id' => $idManguera]);
        return (float) $stmt->fetchColumn();
    }

    public static function obtenerActivas(PDO $conexion): array
    {
        $sql = "SELECT m.id_manguera FROM mangueras m
                JOIN bombas b ON b.id_bomba = m.id_bomba
                WHERE b.estado = 'activa'";
        return $conexion->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }
}
