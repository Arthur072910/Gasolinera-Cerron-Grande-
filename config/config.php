<?php
/**
 * Configuracion general del sistema.
 * Gasolinera "El Cerron Grande" - Sistema POS / Control de Inventario
 */

date_default_timezone_set('America/El_Salvador');

define('NOMBRE_SISTEMA', 'El Cerron Grande');
define('NOMBRE_EMPRESA', 'Gasolinera El Cerron Grande');
define('VERSION_SISTEMA', '1.0.0');
define('BASE_URL', '/gasolinera-cerron-grande/');

// Duracion estandar de un turno operativo, en horas.
define('DURACION_TURNO_HORAS', 8);

// Rutas base del proyecto (utiles cuando se arme el enrutamiento final).
define('RUTA_BASE', dirname(__DIR__));
define('RUTA_VIEWS', RUTA_BASE . '/view');
define('RUTA_ASSETS', RUTA_BASE . '/assets');

// Roles validos del sistema (coinciden en texto con la tabla `roles`).
define('ROLES_VALIDOS', ['administrador', 'cajero', 'despachador']);

// Respaldos de la base de datos (ver Administrador > Respaldos y
// cli/respaldo_automatico.php). La carpeta queda fuera de git
// (.gitignore) porque los .sql llevan datos reales del negocio.
define('RUTA_RESPALDOS', RUTA_BASE . '/storage/respaldos');
define('RUTA_MYSQLDUMP', 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe');
define('RUTA_MYSQL_CLI', 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysql.exe');
