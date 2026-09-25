<?php
/**
 * Layout compartido - Header + Rail de navegacion por rol.
 * Cada vista, antes de incluir este archivo, define:
 *   $tituloPagina    (string)           -> titulo de la pantalla
 *   $subtituloPagina (string, opcional) -> linea de contexto
 *   $vistaActiva ya llega definida desde index.php
 */
$rol = Sesion::rolActual();
$vistaActiva = $vistaActiva ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? NOMBRE_SISTEMA) ?> &middot; <?= NOMBRE_SISTEMA ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/rail.css">
</head>
<body>
<?php
$railClaseAvatar = ['administrador' => 'rail__avatar--administrador', 'cajero' => 'rail__avatar--cajero', 'despachador' => 'rail__avatar--despachador'];
$railNombreActual = Sesion::nombreActual();
$railPartesNombre = preg_split('/\s+/', trim($railNombreActual));
$railIniciales = mb_strtoupper(mb_substr($railPartesNombre[0], 0, 1)) . (count($railPartesNombre) > 1 ? mb_strtoupper(mb_substr(end($railPartesNombre), 0, 1)) : '');
?>
<div class="app-shell">
    <nav class="rail">
        <div>
            <div class="rail__marca">
                <div class="rail__marca-top">
                    <div class="rail__icono">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C12 2 5 10.5 5 15a7 7 0 0 0 14 0c0-4.5-7-13-7-13Z"/></svg>
                    </div>
                    <div>
                        <div class="fuente-display rail__marca-nombre">EL CERRON GRANDE</div>
                        <small>Sistema POS &middot; v<?= VERSION_SISTEMA ?></small>
                    </div>
                </div>
                <div class="rail__estado"><span class="rail__estado-punto"></span> Sistema en linea</div>
            </div>

            <ul class="rail__lista" id="rail-lista">
                <span class="rail__resaltado" id="rail-resaltado"></span>
                <?php if ($rol === 'administrador'): ?>
                    <li><a class="rail__key <?= $vistaActiva === 'dashboard' ? 'activo' : '' ?>" href="index.php?vista=dashboard"><span class="num">1</span> Panel general</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'tanques' ? 'activo' : '' ?>" href="index.php?vista=tanques"><span class="num">2</span> Tanques (Arduino)</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'precios' ? 'activo' : '' ?>" href="index.php?vista=precios"><span class="num">3</span> Precios</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'inventario' ? 'activo' : '' ?>" href="index.php?vista=inventario"><span class="num">4</span> Inventario y tienda</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'proveedores' ? 'activo' : '' ?>" href="index.php?vista=proveedores"><span class="num">5</span> Proveedores y cisternas</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'usuarios' ? 'activo' : '' ?>" href="index.php?vista=usuarios"><span class="num">6</span> Usuarios</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'asistencia' ? 'activo' : '' ?>" href="index.php?vista=asistencia"><span class="num">7</span> Asistencia</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'reportes' ? 'activo' : '' ?>" href="index.php?vista=reportes"><span class="num">8</span> Reportes</a></li>
                <?php elseif ($rol === 'cajero'): ?>
                    <li><a class="rail__key <?= $vistaActiva === 'pos_tienda' ? 'activo' : '' ?>" href="index.php?vista=pos_tienda"><span class="num">1</span> Venta en tienda</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'cierre_caja' ? 'activo' : '' ?>" href="index.php?vista=cierre_caja"><span class="num">2</span> Cierre de caja</a></li>
                <?php elseif ($rol === 'despachador'): ?>
                    <li><a class="rail__key <?= $vistaActiva === 'pos_pista' ? 'activo' : '' ?>" href="index.php?vista=pos_pista"><span class="num">1</span> Despacho en pista</a></li>
                    <li><a class="rail__key <?= $vistaActiva === 'cierre_turno' ? 'activo' : '' ?>" href="index.php?vista=cierre_turno"><span class="num">2</span> Cierre de turno</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="rail__pie">
            <div class="rail__persona">
                <span class="rail__avatar <?= $railClaseAvatar[$rol] ?? '' ?>"><?= htmlspecialchars($railIniciales) ?></span>
                <div>
                    <div class="rail__persona-nombre"><?= htmlspecialchars($railNombreActual) ?></div>
                    <div class="rail__persona-rol"><?= htmlspecialchars(ucfirst((string) $rol)) ?></div>
                </div>
            </div>
            <form method="get" action="index.php">
                <input type="hidden" name="accion" value="logout">
                <button type="submit">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Cerrar sesion
                </button>
            </form>
        </div>
    </nav>

    <main class="main">
        <div class="encabezado-vista">
            <h1><?= htmlspecialchars($tituloPagina ?? '') ?></h1>
            <div class="contexto">
                <?= strtoupper(date('D, d M Y - H:i')) ?><br>
                <?= htmlspecialchars($subtituloPagina ?? '') ?>
            </div>
        </div>

        <?php $flash = Sesion::leerFlash(); ?>
        <?php if ($flash): ?>
        <div class="banner-aviso mostrar<?= $flash['tipo'] === 'error' ? ' banner-aviso--error' : '' ?>">
            <?= htmlspecialchars($flash['mensaje']) ?>
        </div>
        <?php endif; ?>
