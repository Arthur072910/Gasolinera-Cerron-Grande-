<?php
/**
 * Respaldo automatico diario de la base de datos.
 * -----------------------------------------------------------------------
 * Pensado para que lo llame el Programador de tareas de Windows (o cron
 * en un servidor real), NO el navegador — por eso vive fuera de
 * view/index.php y no pide sesion ni rol.
 *
 * Configuracion sugerida en el Programador de tareas de Windows:
 *   Programa/script:        C:\wamp64\bin\php\php8.0.30\php.exe
 *   Argumentos:              "C:\wamp64\www\Gasolinera-Cerron-Grande-\cli\respaldo_automatico.php"
 *   Desencadenador:          Diario, a la hora que prefieran (ej. 2:00 a.m.)
 *
 * Ademas de crear el respaldo del dia, borra los que tengan mas de 30
 * dias para no llenar el disco con respaldos diarios indefinidamente.
 * -----------------------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Respaldo.php';
require_once __DIR__ . '/../controller/RespaldoController.php';

const DIAS_A_CONSERVAR = 30;

try {
    $nombre = RespaldoController::crear();
    echo '[' . date('Y-m-d H:i:s') . "] Respaldo creado: $nombre\n";
} catch (Throwable $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR al crear el respaldo: ' . $e->getMessage() . "\n";
    exit(1);
}

$limite   = time() - (DIAS_A_CONSERVAR * 24 * 3600);
$borrados = 0;
foreach (Respaldo::listar() as $respaldo) {
    if ($respaldo['fecha'] < $limite) {
        Respaldo::eliminar($respaldo['nombre']);
        $borrados++;
    }
}
if ($borrados > 0) {
    echo '[' . date('Y-m-d H:i:s') . "] Respaldos con mas de " . DIAS_A_CONSERVAR . " dias eliminados: $borrados\n";
}

exit(0);
