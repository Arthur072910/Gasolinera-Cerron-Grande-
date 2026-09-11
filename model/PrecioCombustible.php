<?php
/**
 * Modelo: PrecioCombustible
 * Representa la entidad correspondiente de la base de datos 'gasolinera_cerron_grande'.
 * NOTA: Caparazon sin conexion a base de datos todavia.
 *       Los metodos quedan definidos pero pendientes de implementar con PDO.
 */

class PrecioCombustible
{
    public $id_precio;
    public $tipo_combustible;
    public $precio_por_galon;
    public $fecha_inicio_vigencia;
    public $fecha_fin_vigencia;
    public $id_usuario_registro;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function obtenerVigentes(PDO $conexion): array
    {
        $sql = 'SELECT tipo_combustible, precio_por_galon, fecha_inicio_vigencia
                FROM precios_combustible WHERE fecha_fin_vigencia IS NULL
                ORDER BY tipo_combustible';
        return $conexion->query($sql)->fetchAll();
    }

    public static function obtenerVigentesPorTipo(PDO $conexion): array
    {
        $mapa = [];
        foreach (self::obtenerVigentes($conexion) as $fila) {
            $mapa[ucfirst($fila['tipo_combustible'])] = (float) $fila['precio_por_galon'];
        }
        return $mapa;
    }

    public static function obtenerHistorial(PDO $conexion): array
    {
        $sql = 'SELECT p.tipo_combustible, p.precio_por_galon, p.fecha_inicio_vigencia, p.fecha_fin_vigencia, u.nombre AS usuario
                FROM precios_combustible p
                JOIN usuarios u ON u.id_usuario = p.id_usuario_registro
                ORDER BY p.fecha_inicio_vigencia DESC';
        return $conexion->query($sql)->fetchAll();
    }

    /**
     * Cierra el precio vigente de ese combustible (fecha_fin_vigencia)
     * y registra el nuevo como activo, dentro de una sola transaccion.
     */
    public static function registrarNuevo(PDO $conexion, string $tipoCombustible, float $precio, int $idUsuario): void
    {
        $conexion->beginTransaction();
        try {
            $cerrar = $conexion->prepare(
                'UPDATE precios_combustible SET fecha_fin_vigencia = NOW()
                 WHERE tipo_combustible = :tipo AND fecha_fin_vigencia IS NULL'
            );
            $cerrar->execute([':tipo' => $tipoCombustible]);

            $insertar = $conexion->prepare(
                'INSERT INTO precios_combustible (tipo_combustible, precio_por_galon, id_usuario_registro)
                 VALUES (:tipo, :precio, :id_usuario)'
            );
            $insertar->execute([':tipo' => $tipoCombustible, ':precio' => $precio, ':id_usuario' => $idUsuario]);

            $conexion->commit();
        } catch (Exception $e) {
            $conexion->rollBack();
            throw $e;
        }
    }
}
