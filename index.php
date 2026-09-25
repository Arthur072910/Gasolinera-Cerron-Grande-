<?php
/**
 * Punto de entrada del sistema (front controller).
 * Gasolinera "El Cerron Grande"
 *
 * Toda peticion pasa por aqui. Segun el parametro `vista` en la URL
 * y el rol guardado en sesion, se decide que pantalla mostrar.
 *
 * El login (AuthController) valida el PIN contra `usuarios.pin_hash`
 * en la base de datos.
 *
 * Ejemplos:
 *   index.php                      -> decide login o panel segun sesion
 *   index.php?vista=dashboard      -> administrador
 *   index.php?vista=pos_pista      -> despachador
 *   index.php?vista=pos_tienda     -> cajero
 *   index.php?accion=login  (POST) -> procesa el PIN
 *   index.php?accion=logout        -> cierra sesion
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/controller/AuthController.php';

// ---------------- Acciones (formularios que cambian estado) ----------------
$accion = $_GET['accion'] ?? null;

if ($accion === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    if (AuthController::intentarIngreso($pin)) {
        header('Location: index.php');
        exit;
    }
    $errorLogin = 'PIN no reconocido. Intenta de nuevo.';
}

if ($accion === 'logout') {
    AuthController::cerrarSesion();
    header('Location: index.php');
    exit;
}

/**
 * Ejecuta una accion protegida por rol: valida sesion/rol, corre el
 * callback dentro de un try/catch generico y redirige a $vistaVuelta
 * dejando un mensaje flash (exito o el error de negocio lanzado).
 */
function ejecutarAccion(array $rolesPermitidos, string $vistaVuelta, callable $callback): void
{
    Sesion::requerirRol($rolesPermitidos);
    try {
        $mensaje = $callback();
        Sesion::flash('ok', $mensaje ?: 'Operacion realizada correctamente.');
    } catch (RuntimeException $e) {
        Sesion::flash('error', $e->getMessage());
    } catch (Throwable $e) {
        Sesion::flash('error', 'Ocurrio un error al procesar la solicitud.');
    }
    header('Location: index.php?vista=' . $vistaVuelta);
    exit;
}

if ($accion === 'despacho' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/DespachoController.php';
    require_once __DIR__ . '/controller/TurnoController.php';
    ejecutarAccion(['despachador'], 'pos_pista', function () {
        $turno = TurnoController::obtenerOAbrirTurnoActivo('pista');
        $resultado = DespachoController::procesarDespacho(
            (int) ($_POST['id_manguera'] ?? 0),
            (string) ($_POST['modalidad'] ?? 'monto'),
            (float) ($_POST['valor_entrada'] ?? 0),
            (string) ($_POST['metodo_pago'] ?? 'efectivo'),
            $turno['id_turno']
        );
        return sprintf(
            'Despacho registrado: %s %.3f gal por $%.2f (comprobante #%d).',
            $resultado['combustible'], $resultado['galones'], $resultado['total'], $resultado['id_venta']
        );
    });
}

if ($accion === 'venta_tienda' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/TiendaController.php';
    require_once __DIR__ . '/controller/TurnoController.php';
    ejecutarAccion(['cajero'], 'pos_tienda', function () {
        $carrito = json_decode($_POST['carrito'] ?? '[]', true) ?: [];
        $turno = TurnoController::obtenerOAbrirTurnoActivo('tienda');
        $resultado = TiendaController::procesarVenta(
            $carrito,
            (string) ($_POST['metodo_pago'] ?? 'efectivo'),
            (string) ($_POST['tipo_comprobante'] ?? 'ticket'),
            $turno['id_turno']
        );
        return sprintf('Venta cobrada por $%.2f (comprobante #%d).', $resultado['total'], $resultado['id_venta']);
    });
}

if ($accion === 'cerrar_turno' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/TurnoController.php';
    ejecutarAccion(['despachador'], 'cierre_turno', function () {
        $turno = TurnoController::obtenerTurnoParaCierre('pista');
        if ($turno === null || $turno['estado'] !== 'abierto') {
            throw new RuntimeException('No hay un turno de pista abierto para cerrar.');
        }
        TurnoController::cerrarTurnoActual($turno['id_turno'], (float) ($_POST['monto_declarado'] ?? 0), 'pista');
        return 'Turno cerrado y lecturas de manguera conciliadas.';
    });
}

if ($accion === 'cerrar_caja' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/TurnoController.php';
    ejecutarAccion(['cajero'], 'cierre_caja', function () {
        $turno = TurnoController::obtenerTurnoParaCierre('tienda');
        if ($turno === null || $turno['estado'] !== 'abierto') {
            throw new RuntimeException('No hay una caja de tienda abierta para cerrar.');
        }
        TurnoController::cerrarTurnoActual($turno['id_turno'], (float) ($_POST['monto_declarado'] ?? 0), 'tienda');
        return 'Cierre de caja registrado.';
    });
}

if ($accion === 'precio_nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/PrecioController.php';
    ejecutarAccion(['administrador'], 'precios', function () {
        PrecioController::registrarNuevoPrecio(
            (string) ($_POST['combustible'] ?? ''),
            (float) ($_POST['precio'] ?? 0),
            Sesion::idUsuarioActual()
        );
        return 'Precio actualizado. El anterior quedo cerrado para auditoria.';
    });
}

if ($accion === 'producto_nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/TiendaController.php';
    require_once __DIR__ . '/model/Producto.php';
    require_once __DIR__ . '/config/database.php';
    ejecutarAccion(['administrador'], 'inventario', function () {
        Producto::crear(
            Database::obtenerConexion(),
            (int) ($_POST['id_categoria'] ?? 0),
            trim((string) ($_POST['codigo_barras'] ?? '')),
            (string) ($_POST['nombre'] ?? ''),
            (float) ($_POST['precio'] ?? 0),
            (int) ($_POST['stock'] ?? 0),
            (int) ($_POST['stock_minimo'] ?? 5)
        );
        return 'Producto registrado en el catalogo.';
    });
}

if ($accion === 'categoria_nueva' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/model/Categoria.php';
    require_once __DIR__ . '/config/database.php';
    ejecutarAccion(['administrador'], 'inventario', function () {
        Categoria::crear(Database::obtenerConexion(), (string) ($_POST['nombre'] ?? ''));
        return 'Categoria agregada.';
    });
}

if ($accion === 'proveedor_nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/ProveedorController.php';
    ejecutarAccion(['administrador'], 'proveedores', function () {
        ProveedorController::registrarProveedor(
            (string) ($_POST['nombre'] ?? ''),
            (string) ($_POST['registro_fiscal'] ?? ''),
            trim((string) ($_POST['telefono'] ?? '')) ?: null
        );
        return 'Proveedor registrado.';
    });
}

if ($accion === 'recepcion_nueva' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/ProveedorController.php';
    ejecutarAccion(['administrador'], 'proveedores', function () {
        ProveedorController::registrarRecepcion(
            (int) ($_POST['id_proveedor'] ?? 0),
            (int) ($_POST['id_tanque'] ?? 0),
            (string) ($_POST['numero_factura'] ?? ''),
            (float) ($_POST['galones_facturados'] ?? 0),
            (float) ($_POST['galones_medidos'] ?? 0),
            (float) ($_POST['costo_total'] ?? 0)
        );
        return 'Recepcion de cisterna registrada.';
    });
}

if ($accion === 'usuario_nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/UsuarioController.php';
    Sesion::flashDatos([
        'modo'   => 'crear',
        'nombre' => (string) ($_POST['nombre'] ?? ''),
        'id_rol' => (string) ($_POST['id_rol'] ?? ''),
    ]);
    ejecutarAccion(['administrador'], 'usuarios', function () {
        if (($_POST['pin'] ?? '') !== ($_POST['pin_confirmar'] ?? '')) {
            throw new RuntimeException('El PIN y su confirmacion no coinciden.');
        }
        UsuarioController::registrarUsuario(
            (int) ($_POST['id_rol'] ?? 0),
            (string) ($_POST['nombre'] ?? ''),
            (string) ($_POST['pin'] ?? '')
        );
        return 'Usuario registrado.';
    });
}

if ($accion === 'usuario_editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/UsuarioController.php';
    Sesion::flashDatos([
        'modo'   => 'editar',
        'id'     => (string) ($_POST['id_usuario'] ?? ''),
        'nombre' => (string) ($_POST['nombre'] ?? ''),
        'id_rol' => (string) ($_POST['id_rol'] ?? ''),
    ]);
    ejecutarAccion(['administrador'], 'usuarios', function () {
        $pin = trim((string) ($_POST['pin'] ?? ''));
        $pinConfirmar = trim((string) ($_POST['pin_confirmar'] ?? ''));
        if ($pin !== $pinConfirmar) {
            throw new RuntimeException('El PIN y su confirmacion no coinciden.');
        }
        UsuarioController::editarUsuario(
            (int) ($_POST['id_usuario'] ?? 0),
            (int) ($_POST['id_rol'] ?? 0),
            (string) ($_POST['nombre'] ?? ''),
            $pin !== '' ? $pin : null
        );
        return 'Usuario actualizado.';
    });
}

if ($accion === 'usuario_estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/UsuarioController.php';
    ejecutarAccion(['administrador'], 'usuarios', function () {
        UsuarioController::cambiarEstado((int) ($_POST['id_usuario'] ?? 0), (string) ($_POST['estado'] ?? 'inactivo'));
        return 'Estado del usuario actualizado.';
    });
}

if ($accion === 'tanque_lectura' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controller/TanqueController.php';
    ejecutarAccion(['administrador'], 'tanques', function () {
        TanqueController::registrarLecturaManual(
            (int) ($_POST['id_tanque'] ?? 0),
            (float) ($_POST['nivel_cm'] ?? 0),
            (float) ($_POST['galones'] ?? 0)
        );
        return 'Lectura de tanque registrada (simulacion manual).';
    });
}

// Exportacion de reportes: responden con el archivo directo (PDF/CSV),
// no con una vista HTML, asi que no pasan por ejecutarAccion().
if ($accion === 'reporte_pdf' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Sesion::requerirRol(['administrador']);
    require_once __DIR__ . '/controller/ReporteController.php';
    ReporteController::generarPdf();
    exit;
}

if ($accion === 'reporte_csv' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    Sesion::requerirRol(['administrador']);
    require_once __DIR__ . '/controller/ReporteController.php';
    ReporteController::generarCsv();
    exit;
}

// ---------------- Vistas (GET) ----------------

// Sin sesion activa: solo se permite ver el login.
if (!Sesion::estaActiva()) {
    require __DIR__ . '/view/auth/login.php';
    exit;
}

$rol = Sesion::rolActual();

// Vista por defecto segun el rol si no se especifica ninguna.
$vistasPorDefecto = [
    'administrador' => 'dashboard',
    'cajero'        => 'pos_tienda',
    'despachador'   => 'pos_pista',
];

$vista = $_GET['vista'] ?? $vistasPorDefecto[$rol];

$rutasPermitidas = [
    'administrador' => [
        'dashboard'   => '/view/admin/dashboard.php',
        'tanques'     => '/view/admin/tanque_monitor.php',
        'precios'     => '/view/admin/precios.php',
        'inventario'  => '/view/admin/inventario.php',
        'proveedores' => '/view/admin/proveedores.php',
        'usuarios'    => '/view/admin/usuarios.php',
        'asistencia'  => '/view/admin/asistencia.php',
        'reportes'    => '/view/admin/reportes.php',
    ],
    'cajero' => [
        'pos_tienda'  => '/view/cajero/pos_tienda.php',
        'cierre_caja' => '/view/cajero/cierre_caja.php',
    ],
    'despachador' => [
        'pos_pista'    => '/view/despachador/pos_pista.php',
        'cierre_turno' => '/view/despachador/cierre_turno.php',
    ],
];

if (!isset($rutasPermitidas[$rol][$vista])) {
    http_response_code(404);
    echo '<pre style="font-family:monospace">404 - Vista no encontrada para este rol.</pre>';
    exit;
}

$vistaActiva = $vista;
require __DIR__ . $rutasPermitidas[$rol][$vista];
