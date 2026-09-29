<?php
/**
 * Modelo: Bitacora
 * Registro de actividad de cada usuario (que accion hizo, cuando).
 * Se alimenta automaticamente desde index.php::ejecutarAccion() para
 * cubrir toda mutacion de negocio (ventas, cierres, precios, usuarios,
 * inventario, proveedores, tanques) sin tener que instrumentar cada
 * controlador por separado; login/logout se registran aparte porque no
 * pasan por ejecutarAccion().
 */

class Bitacora
{
    public $id_bitacora;
    public $id_usuario;
    public $accion;
    public $descripcion;
    public $fecha_hora;

    public function __construct(array $datos = [])
    {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public static function registrar(PDO $conexion, int $idUsuario, string $accion, string $descripcion): void
    {
        $stmt = $conexion->prepare(
            'INSERT INTO bitacora (id_usuario, accion, descripcion) VALUES (:id_usuario, :accion, :descripcion)'
        );
        $stmt->execute([
            ':id_usuario'  => $idUsuario,
            ':accion'      => $accion,
            ':descripcion' => mb_substr($descripcion, 0, 255),
        ]);
    }

    /**
     * @param array $filtros claves opcionales: id_usuario, accion, desde (Y-m-d), hasta (Y-m-d)
     */
    public static function obtenerFiltrada(PDO $conexion, array $filtros = [], int $limite = 200): array
    {
        $condiciones = [];
        $parametros  = [];

        if (!empty($filtros['id_usuario'])) {
            $condiciones[] = 'b.id_usuario = :id_usuario';
            $parametros[':id_usuario'] = (int) $filtros['id_usuario'];
        }
        if (!empty($filtros['accion'])) {
            $condiciones[] = 'b.accion = :accion';
            $parametros[':accion'] = $filtros['accion'];
        }
        if (!empty($filtros['desde'])) {
            $condiciones[] = 'DATE(b.fecha_hora) >= :desde';
            $parametros[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $condiciones[] = 'DATE(b.fecha_hora) <= :hasta';
            $parametros[':hasta'] = $filtros['hasta'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT b.id_bitacora, b.accion, b.descripcion, b.fecha_hora,
                       u.nombre AS usuario, r.nombre_rol AS rol
                FROM bitacora b
                JOIN usuarios u ON u.id_usuario = b.id_usuario
                JOIN roles r ON r.id_rol = u.id_rol
                $where
                ORDER BY b.fecha_hora DESC, b.id_bitacora DESC
                LIMIT " . max(1, $limite);

        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /** Lista de acciones distintas ya registradas, para poblar el filtro. */
    public static function obtenerAccionesDistintas(PDO $conexion): array
    {
        return $conexion->query('SELECT DISTINCT accion FROM bitacora ORDER BY accion')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Usuarios que tienen al menos un registro, para poblar el filtro. */
    public static function obtenerUsuariosConRegistros(PDO $conexion): array
    {
        $sql = 'SELECT DISTINCT u.id_usuario AS id, u.nombre
                FROM bitacora b
                JOIN usuarios u ON u.id_usuario = b.id_usuario
                ORDER BY u.nombre';
        return $conexion->query($sql)->fetchAll();
    }

    public static function contarHoy(PDO $conexion): int
    {
        return (int) $conexion->query("SELECT COUNT(*) FROM bitacora WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();
    }
}
