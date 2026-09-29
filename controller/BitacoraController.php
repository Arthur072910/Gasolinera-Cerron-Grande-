<?php
/**
 * Controlador: Bitacora de actividad (solo Administrador)
 * Traduce los codigos de accion internos (los mismos que usa el
 * enrutador en index.php) a etiquetas legibles para la vista.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Bitacora.php';

class BitacoraController
{
    private const ETIQUETAS = [
        'login'           => 'Inicio de sesion',
        'logout'          => 'Cierre de sesion',
        'despacho'        => 'Despacho de combustible',
        'abrir_caja'      => 'Apertura de caja',
        'venta_tienda'    => 'Venta en tienda',
        'cerrar_turno'    => 'Cierre de turno (pista)',
        'cerrar_caja'     => 'Cierre de caja (tienda)',
        'precio_nuevo'    => 'Cambio de precio',
        'producto_nuevo'  => 'Producto creado',
        'producto_editar' => 'Producto editado',
        'producto_stock'  => 'Ajuste de stock',
        'producto_eliminar' => 'Producto eliminado',
        'categoria_nueva' => 'Categoria creada',
        'proveedor_nuevo' => 'Proveedor creado',
        'proveedor_editar' => 'Proveedor editado',
        'proveedor_eliminar' => 'Proveedor eliminado',
        'recepcion_nueva' => 'Recepcion de cisterna',
        'usuario_nuevo'   => 'Usuario creado',
        'usuario_editar'  => 'Usuario editado',
        'usuario_estado'  => 'Estado de usuario cambiado',
        'tanque_lectura'  => 'Lectura manual de tanque',
    ];

    public static function registrar(string $accion, string $descripcion): void
    {
        $idUsuario = Sesion::idUsuarioActual();
        if ($idUsuario === null) {
            return;
        }
        Bitacora::registrar(Database::obtenerConexion(), $idUsuario, $accion, $descripcion);
    }

    public static function etiqueta(string $accion): string
    {
        return self::ETIQUETAS[$accion] ?? ucfirst(str_replace('_', ' ', $accion));
    }

    /**
     * @param array $filtros claves opcionales: id_usuario, accion, desde, hasta
     */
    public static function listar(array $filtros = []): array
    {
        $conexion = Database::obtenerConexion();
        $filas    = Bitacora::obtenerFiltrada($conexion, $filtros, 300);

        return array_map(fn ($f) => [
            'id'          => (int) $f['id_bitacora'],
            'usuario'     => $f['usuario'],
            'rol'         => $f['rol'],
            'accion'      => $f['accion'],
            'accion_texto' => self::etiqueta($f['accion']),
            'descripcion' => $f['descripcion'],
            'fecha'       => $f['fecha_hora'],
        ], $filas);
    }

    public static function opcionesFiltro(): array
    {
        $conexion = Database::obtenerConexion();
        $acciones = Bitacora::obtenerAccionesDistintas($conexion);

        return [
            'usuarios' => Bitacora::obtenerUsuariosConRegistros($conexion),
            'acciones' => array_map(fn ($a) => ['id' => $a, 'nombre' => self::etiqueta($a)], $acciones),
        ];
    }

    public static function totalHoy(): int
    {
        return Bitacora::contarHoy(Database::obtenerConexion());
    }
}
