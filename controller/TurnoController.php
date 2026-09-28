<?php
/**
 * Controlador: Turnos y Cajas
 * Gestiona apertura/cierre de caja de 8 horas para Cajero y Despachador.
 * Un turno se abre automaticamente la primera vez que el usuario entra
 * a su pantalla de POS del dia, y se cierra explicitamente desde
 * cierre_caja.php / cierre_turno.php.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../model/Turno.php';
require_once __DIR__ . '/../model/Manguera.php';
require_once __DIR__ . '/../model/LecturaTurno.php';

class TurnoController
{
    private const MONTO_INICIAL_DEFECTO = 20.00;

    /**
     * Devuelve el turno abierto del usuario actual; si no existe, lo
     * abre (y, para caja de pista, toma la lectura inicial de cada
     * manguera activa).
     */
    public static function obtenerOAbrirTurnoActivo(string $tipoCaja): array
    {
        $conexion   = Database::obtenerConexion();
        $idUsuario  = Sesion::idUsuarioActual();

        $turno = Turno::obtenerAbiertoPorUsuario($conexion, $idUsuario);

        if ($turno === null) {
            $idTurno = Turno::abrir($conexion, Sesion::idAsistenciaActual(), $tipoCaja, self::MONTO_INICIAL_DEFECTO);

            if ($tipoCaja === 'pista') {
                foreach (Manguera::obtenerActivas($conexion) as $idManguera) {
                    $contadorInicial = Manguera::obtenerContadorActual($conexion, (int) $idManguera);
                    LecturaTurno::abrirParaTurno($conexion, $idTurno, (int) $idManguera, $contadorInicial);
                }
            }

            $turno = Turno::obtenerAbiertoPorUsuario($conexion, $idUsuario);
        }

        return [
            'id_turno'       => (int) $turno['id_turno'],
            'usuario'        => Sesion::nombreActual(),
            'tipo_caja'      => $turno['tipo_caja'],
            'hora_inicio'    => date('H:i:s', strtotime($turno['fecha_inicio'])),
            'hora_prevista'  => date('H:i:s', strtotime($turno['fecha_inicio']) + DURACION_TURNO_HORAS * 3600),
            'monto_inicial'  => (float) $turno['monto_inicial'],
            'estado'         => $turno['estado'],
        ];
    }

    /**
     * Para pantallas de conciliacion (cierre_caja/cierre_turno): NUNCA
     * abre un turno nuevo. Devuelve el abierto si existe; si no, el
     * ultimo que tuvo el usuario (para poder ver su recibo tras
     * cerrarlo); null si nunca ha tenido ninguno.
     */
    public static function obtenerTurnoParaCierre(string $tipoCaja): ?array
    {
        $conexion  = Database::obtenerConexion();
        $idUsuario = Sesion::idUsuarioActual();

        $turno = Turno::obtenerAbiertoPorUsuario($conexion, $idUsuario)
              ?? Turno::obtenerUltimoPorUsuario($conexion, $idUsuario, $tipoCaja);

        if ($turno === null) {
            return null;
        }

        return [
            'id_turno'        => (int) $turno['id_turno'],
            'tipo_caja'       => $turno['tipo_caja'],
            'estado'          => $turno['estado'],
            'monto_inicial'   => (float) $turno['monto_inicial'],
            'monto_declarado' => $turno['monto_declarado'] !== null ? (float) $turno['monto_declarado'] : null,
        ];
    }

    /** Turnos abiertos ahora mismo (quien esta trabajando), para el panel general. */
    public static function turnosActivos(): array
    {
        $filas = Turno::obtenerAbiertosConUsuario(Database::obtenerConexion());
        return array_map(fn ($t) => [
            'usuario'       => $t['usuario'],
            'rol'           => $t['rol'],
            'tipo_caja'     => $t['tipo_caja'] === 'pista' ? 'Pista' : 'Tienda',
            'hora_inicio'   => date('H:i', strtotime($t['fecha_inicio'])),
            'monto_inicial' => (float) $t['monto_inicial'],
        ], $filas);
    }

    public static function cerrarTurnoActual(int $idTurno, float $montoDeclarado, string $tipoCaja): void
    {
        if ($montoDeclarado < 0) {
            throw new RuntimeException('El monto declarado no puede ser negativo.');
        }
        if ($montoDeclarado > 100000) {
            throw new RuntimeException('Ese monto declarado parece un error de digitacion.');
        }

        $conexion = Database::obtenerConexion();

        if ($tipoCaja === 'pista') {
            LecturaTurno::cerrarTodasDelTurno($conexion, $idTurno);
        }

        Turno::cerrar($conexion, $idTurno, $montoDeclarado);
    }

    /**
     * Resumen completo para la pantalla de conciliacion de caja de tienda:
     * separa el efectivo (lo unico que realmente esta fisicamente en la
     * gaveta) del resto de metodos de pago, para que la diferencia se
     * calcule contra lo que el cajero de verdad deberia tener en mano
     * (fondo inicial + ventas en efectivo), no contra el total de ventas
     * (que incluye tarjeta/mixto, dinero que nunca paso por la caja).
     */
    public static function obtenerResumenCierre(string $tipoCaja): ?array
    {
        $turnoBase = self::obtenerTurnoParaCierre($tipoCaja);
        if ($turnoBase === null) {
            return null;
        }

        $conexion      = Database::obtenerConexion();
        $ventasPorPago = Turno::obtenerVentasPorTipoPago($conexion, $turnoBase['id_turno']);
        $totalTurno    = Turno::totalVentas($conexion, $turnoBase['id_turno']);

        $ventasEfectivo = 0.0;
        foreach ($ventasPorPago as $v) {
            if ($v['metodo_pago'] === 'efectivo') {
                $ventasEfectivo = (float) $v['total'];
                break;
            }
        }

        $efectivoEsperado = round($turnoBase['monto_inicial'] + $ventasEfectivo, 2);

        return $turnoBase + [
            'ventas_por_pago'    => $ventasPorPago,
            'total_turno'        => $totalTurno,
            'ventas_efectivo'    => $ventasEfectivo,
            'efectivo_esperado'  => $efectivoEsperado,
            'diferencia'         => $turnoBase['monto_declarado'] !== null
                ? round($turnoBase['monto_declarado'] - $efectivoEsperado, 2)
                : null,
        ];
    }
}
