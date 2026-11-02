<?php
/**
 * Disponibilidad.php  (v8)
 *
 * Servicio compartido por Reservas y Préstamos: calcula cuánto stock de un TIPO de
 * material (material.nombre) está libre en una franja de tiempo.
 *
 *   capacidad   unidades que existen y están en servicio (ni dadas de baja ni en mantenimiento)
 *   reservadas  máximo de unidades reservadas a la vez (reservas APROBADAS o ACTIVAS) dentro de la franja
 *   prestadas   máximo de unidades en préstamo a la vez durante la franja:
 *                 ACTIVO    [fecha_entrega, fecha_entrega + horas]
 *                 APROBADO  [fecha_validacion, fecha_validacion + horas]
 *               Si el plazo ya venció y el material no volvió (o no se retiró), se sigue contando
 *               hasta el final del día de hoy: físicamente no está en el CeiLab.
 *   libres      capacidad - máxima ocupación simultánea de reservas y préstamos juntos (nunca menor que 0)
 *
 * Regla de superposición (la misma de siempre): dos franjas se pisan si
 *   inicioA < finB  Y  finA > inicioB   (si una termina justo cuando la otra empieza, NO se pisan)
 *
 * Las reservas tienen prioridad sobre los préstamos: un préstamo no puede ocupar stock
 * que una reserva aprobada necesita en esa franja (ver Modelo_Prestamo).
 */
require_once __DIR__ . '/Estados.php';

/** Error de negocio con detalle estructurado (stock por producto, próximos a liberarse). */
class ErrorDisponibilidad extends ErrorNegocio {
    const SIN_STOCK     = 'SIN_STOCK';       // nada disponible para lo pedido
    const STOCK_PARCIAL = 'STOCK_PARCIAL';   // hay una parte, el cliente debe confirmar

    public $codigo;
    public $detalle;

    public function __construct($codigo, $mensaje, array $detalle = [], array $errores = []) {
        parent::__construct($mensaje, $errores);
        $this->codigo  = $codigo;
        $this->detalle = $detalle;
    }
}

class Disponibilidad {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /** «Ahora» en la hora de Uruguay (no depende de la zona horaria del servidor de base de datos). */
    public static function ahora() {
        return new DateTimeImmutable('now', new DateTimeZone('America/Montevideo'));
    }

    /** 'Y-m-d' + 'HH:MM[:SS]' -> 'Y-m-d H:i:s' */
    public static function momento($fecha, $hora) {
        return $fecha . ' ' . substr($hora . ':00', 0, 8);
    }

    /* ------------------------------------------------------------------
       CAPACIDAD
       ------------------------------------------------------------------ */

    /** @return array ['existe', 'tipo_control', 'categoria', 'capacidad', 'total', 'en_mantenimiento'] */
    public function capacidad($nombre) {
        $st = $this->db->prepare(
            "SELECT tipo_control, MIN(categoria) AS categoria,
                    SUM(CASE WHEN estado NOT IN ('DADO_DE_BAJA', 'EN_MANTENIMIENTO') THEN cantidad_total ELSE 0 END) AS capacidad,
                    SUM(CASE WHEN estado <> 'DADO_DE_BAJA' THEN cantidad_total ELSE 0 END) AS total,
                    SUM(CASE WHEN estado = 'EN_MANTENIMIENTO' THEN cantidad_total ELSE 0 END) AS en_mantenimiento
               FROM material
              WHERE nombre = ? AND estado <> 'DADO_DE_BAJA'
              GROUP BY tipo_control");
        $st->execute([$nombre]);
        $f = $st->fetch(PDO::FETCH_ASSOC);
        if (!$f) {
            return ['existe' => false, 'tipo_control' => null, 'categoria' => null,
                    'capacidad' => 0, 'total' => 0, 'en_mantenimiento' => 0];
        }
        return ['existe' => true, 'tipo_control' => $f['tipo_control'], 'categoria' => $f['categoria'],
                'capacidad' => (int)$f['capacidad'], 'total' => (int)$f['total'],
                'en_mantenimiento' => (int)$f['en_mantenimiento']];
    }

    /* ------------------------------------------------------------------
       OCUPACIÓN EN UNA FRANJA
       ------------------------------------------------------------------ */

    /**
     * Intervalos de las reservas APROBADAS o ACTIVAS del tipo que se superponen con [desde, hasta).
     * @return array [['inicio', 'fin', 'cantidad', 'id_solicitud'], ...]
     */
    public function intervalosReserva($nombre, $desde, $hasta, $excluirSolicitud = 0) {
        $st = $this->db->prepare(
            "SELECT rp.id_solicitud, rp.cantidad,
                    TIMESTAMP(r.fecha, r.hora_inicio) AS inicio, TIMESTAMP(r.fecha, r.hora_fin) AS fin
               FROM reserva_pedido rp
               JOIN solicitud s ON s.id_solicitud = rp.id_solicitud
               JOIN reserva r   ON r.id_solicitud = rp.id_solicitud
              WHERE rp.nombre_material = ? AND s.estado IN ('APROBADO', 'ACTIVO') AND s.id_solicitud <> ?
                AND TIMESTAMP(r.fecha, r.hora_inicio) < ? AND TIMESTAMP(r.fecha, r.hora_fin) > ?");
        $st->execute([$nombre, (int)$excluirSolicitud, $hasta, $desde]);
        return array_map(function ($f) {
            return ['inicio' => $f['inicio'], 'fin' => $f['fin'], 'cantidad' => (int)$f['cantidad'],
                    'id_solicitud' => (int)$f['id_solicitud']];
        }, $st->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Máximo de unidades reservadas A LA VEZ dentro de [desde, hasta). */
    public function reservado($nombre, $desde, $hasta, $excluirSolicitud = 0) {
        return self::pico($this->intervalosReserva($nombre, $desde, $hasta, $excluirSolicitud), $desde, $hasta);
    }

    /** Máximo de unidades en préstamo A LA VEZ dentro de [desde, hasta). */
    public function prestado($nombre, $desde, $hasta, DateTimeInterface $ahora, $excluirSolicitud = 0) {
        $ventanas = $this->ventanasPrestamo($nombre, $ahora->format('Y-m-d H:i:s'), $excluirSolicitud);
        return self::pico($ventanas, $desde, $hasta);
    }

    /**
     * Máxima ocupación simultánea de un conjunto de intervalos, medida solo dentro de [desde, hasta).
     * Las reservas no ocupan stock todas a la vez: una de 10 a 11 y otra de 11 a 12 nunca coinciden.
     * Se ordenan los instantes en que empieza o termina cada intervalo y se va sumando: al llegar una hora,
     * primero se restan los que terminan y después se suman los que empiezan (así «12:00 y 12:00» no se pisan).
     */
    public static function pico(array $intervalos, $desde, $hasta) {
        $eventos = [];
        foreach ($intervalos as $i) {
            $ini = max($i['inicio'], $desde);
            $fin = min($i['fin'], $hasta);
            if ($ini >= $fin) continue;
            $eventos[] = [$ini, 1, (int)$i['cantidad']];     // 1 = empieza
            $eventos[] = [$fin, 0, -(int)$i['cantidad']];    // 0 = termina (va primero en un empate)
        }
        usort($eventos, function ($a, $b) {
            return $a[0] === $b[0] ? $a[1] <=> $b[1] : strcmp($a[0], $b[0]);
        });
        $actual = 0;
        $maximo = 0;
        foreach ($eventos as $e) {
            $actual += $e[2];
            if ($actual > $maximo) $maximo = $actual;
        }
        return $maximo;
    }

    /**
     * Ventanas de tiempo de los préstamos que ocupan stock de este tipo.
     * @return array [['id_solicitud', 'estado', 'cantidad', 'inicio', 'fin'], ...]
     */
    public function ventanasPrestamo($nombre, $ahoraTxt, $excluirSolicitud = 0) {
        $ventanas = [];

        // Entregados y sin devolver: ocupan desde la entrega hasta que vence el plazo (o hasta ahora si ya venció).
        $st = $this->db->prepare(
            "SELECT s.id_solicitud, SUM(pm.cantidad - COALESCE(pm.cantidad_devuelta, 0)) AS cantidad,
                    p.fecha_entrega AS inicio,
                    DATE_ADD(p.fecha_entrega, INTERVAL COALESCE(p.horas_solicitadas, 0) HOUR) AS fin_prevista
               FROM prestamo_material pm
               JOIN material m  ON m.id_material = pm.id_material
               JOIN solicitud s ON s.id_solicitud = pm.id_solicitud AND s.estado = 'ACTIVO'
               JOIN prestamo p  ON p.id_solicitud = pm.id_solicitud
              WHERE m.nombre = ? AND pm.fecha_devolucion IS NULL AND p.fecha_entrega IS NOT NULL AND s.id_solicitud <> ?
              GROUP BY s.id_solicitud, p.fecha_entrega, p.horas_solicitadas");
        $st->execute([$nombre, (int)$excluirSolicitud]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
            if ((int)$f['cantidad'] <= 0) continue;
            $ventanas[] = ['id_solicitud' => (int)$f['id_solicitud'], 'estado' => 'ACTIVO',
                           'cantidad' => (int)$f['cantidad'], 'inicio' => $f['inicio'],
                           'fin' => $this->finVigente($f['fin_prevista'], $ahoraTxt)];
        }

        // Aprobados y todavía sin entregar: se cuentan desde que se aprobaron.
        $st = $this->db->prepare(
            "SELECT s.id_solicitud, pp.cantidad, s.fecha_validacion AS inicio,
                    DATE_ADD(s.fecha_validacion, INTERVAL COALESCE(p.horas_solicitadas, 0) HOUR) AS fin_prevista
               FROM prestamo_pedido pp
               JOIN solicitud s ON s.id_solicitud = pp.id_solicitud AND s.estado = 'APROBADO'
               JOIN prestamo p  ON p.id_solicitud = pp.id_solicitud
              WHERE pp.nombre_material = ? AND s.fecha_validacion IS NOT NULL AND s.id_solicitud <> ?");
        $st->execute([$nombre, (int)$excluirSolicitud]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $ventanas[] = ['id_solicitud' => (int)$f['id_solicitud'], 'estado' => 'APROBADO',
                           'cantidad' => (int)$f['cantidad'], 'inicio' => $f['inicio'],
                           'fin' => $this->finVigente($f['fin_prevista'], $ahoraTxt)];
        }
        return $ventanas;
    }

    /** Fin de la ventana: el previsto; si ya pasó, hasta el final del día de hoy. */
    private function finVigente($finPrevisto, $ahoraTxt) {
        return $finPrevisto >= $ahoraTxt ? $finPrevisto : substr($ahoraTxt, 0, 10) . ' 23:59:59';
    }

    /* ------------------------------------------------------------------
       EVALUACIÓN COMPLETA DE UNA FRANJA
       ------------------------------------------------------------------ */

    /**
     * Stock de un tipo en la franja [desde, hasta) con los próximos a liberarse.
     * @param string $desde 'Y-m-d H:i:s'
     * @param string $hasta 'Y-m-d H:i:s'
     */
    public function evaluar($nombre, $desde, $hasta, $excluirSolicitud = 0, DateTimeInterface $ahora = null) {
        $ahora = $ahora ?: self::ahora();
        $cap  = $this->capacidad($nombre);
        $resv = $this->intervalosReserva($nombre, $desde, $hasta, $excluirSolicitud);
        $prest = $this->ventanasPrestamo($nombre, $ahora->format('Y-m-d H:i:s'), $excluirSolicitud);
        $res  = self::pico($resv, $desde, $hasta);
        $pre  = self::pico($prest, $desde, $hasta);
        $uso  = self::pico(array_merge($resv, $prest), $desde, $hasta);   // lo ocupado a la vez por ambas cosas
        $libres = max(0, $cap['capacidad'] - $uso);
        return [
            'nombre'           => $nombre,
            'existe'           => $cap['existe'],
            'tipo_control'     => $cap['tipo_control'],
            'categoria'        => $cap['categoria'],
            'capacidad'        => $cap['capacidad'],
            'en_mantenimiento' => $cap['en_mantenimiento'],
            'reservadas'       => $res,
            'prestadas'        => $pre,
            'libres'           => $libres,
            'proximos'         => $this->proximosALiberarse($nombre, $desde, $hasta, $excluirSolicitud, $ahora),
        ];
    }

    /**
     * Cantidades que se liberan DENTRO de la franja (terminan después de que empieza y antes de que termine),
     * ordenadas por hora. Sirve para decirle al cliente cuándo habrá más stock.
     * @return array [['hora' => 'HH:MM', 'fecha' => 'Y-m-d', 'cantidad' => n, 'origen' => 'reserva'|'prestamo', 'id_solicitud' => n], ...]
     */
    public function proximosALiberarse($nombre, $desde, $hasta, $excluirSolicitud, DateTimeInterface $ahora) {
        $eventos = [];

        $st = $this->db->prepare(
            "SELECT rp.id_solicitud, rp.cantidad,
                    TIMESTAMP(r.fecha, r.hora_inicio) AS inicio, TIMESTAMP(r.fecha, r.hora_fin) AS fin
               FROM reserva_pedido rp
               JOIN solicitud s ON s.id_solicitud = rp.id_solicitud
               JOIN reserva r   ON r.id_solicitud = rp.id_solicitud
              WHERE rp.nombre_material = ? AND s.estado IN ('APROBADO', 'ACTIVO') AND s.id_solicitud <> ?
                AND TIMESTAMP(r.fecha, r.hora_inicio) < ? AND TIMESTAMP(r.fecha, r.hora_fin) > ?");
        $st->execute([$nombre, (int)$excluirSolicitud, $hasta, $desde]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
            if ($f['fin'] < $hasta) {
                $eventos[] = ['momento' => $f['fin'], 'cantidad' => (int)$f['cantidad'],
                              'origen' => 'reserva', 'id_solicitud' => (int)$f['id_solicitud']];
            }
        }
        foreach ($this->ventanasPrestamo($nombre, $ahora->format('Y-m-d H:i:s'), $excluirSolicitud) as $v) {
            if ($v['inicio'] < $hasta && $v['fin'] > $desde && $v['fin'] < $hasta) {
                $eventos[] = ['momento' => $v['fin'], 'cantidad' => $v['cantidad'],
                              'origen' => 'prestamo', 'id_solicitud' => $v['id_solicitud']];
            }
        }
        usort($eventos, function ($a, $b) { return strcmp($a['momento'], $b['momento']); });

        return array_map(function ($e) {
            return ['fecha' => substr($e['momento'], 0, 10), 'hora' => substr($e['momento'], 11, 5),
                    'cantidad' => $e['cantidad'], 'origen' => $e['origen'], 'id_solicitud' => $e['id_solicitud']];
        }, $eventos);
    }

    /**
     * Primera hora (dentro de la franja) desde la cual habrá `faltan` unidades más libres,
     * sumando lo que se va liberando. null si dentro de la franja nunca se alcanza.
     */
    public static function completoDesde(array $proximos, $faltan) {
        $acumulado = 0;
        foreach ($proximos as $p) {
            $acumulado += $p['cantidad'];
            if ($acumulado >= $faltan) return $p['hora'];
        }
        return null;
    }
}
