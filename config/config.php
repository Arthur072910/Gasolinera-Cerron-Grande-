<?php
/**
 * Configuracion general del sistema.
 * Gasolinera "El Cerron Grande" - Sistema POS / Control de Inventario
 *
 * NOTA: Este caparazon todavia no tiene conexion a base de datos.
 * Cuando se conecte MySQL, este archivo se complementara con database.php.
 */

date_default_timezone_set('America/El_Salvador');

define('NOMBRE_SISTEMA', 'El Cerron Grande');
define('NOMBRE_EMPRESA', 'Gasolinera El Cerron Grande');
define('VERSION_SISTEMA', '0.1.0 - Caparazon de diseno');
define('BASE_URL', '/gasolinera-cerron-grande/');

// Duracion estandar de un turno operativo, en horas.
define('DURACION_TURNO_HORAS', 8);

// Rutas base del proyecto (utiles cuando se arme el enrutamiento final).
define('RUTA_BASE', dirname(__DIR__));
define('RUTA_VIEWS', RUTA_BASE . '/view');
define('RUTA_ASSETS', RUTA_BASE . '/assets');

// Roles validos del sistema (coinciden en texto con la tabla `roles`).
define('ROLES_VALIDOS', ['administrador', 'cajero', 'despachador']);
