<?php
/**
 * Modelo_Reservas.php  (v7)
 *
 * Reserva de equipos específicos (RF37 a RF40) para una fecha y una franja horaria.
 *
 * Ciclo de una reserva (usa los mismos estados que los préstamos, ver Config/Estados.php):
 *   1. solicitar  (cliente) -> PENDIENTE. Elige uno o más equipos concretos (por id_material),
 *                              una fecha y una franja «desde - hasta».
 *   2. aprobar    (admin)   -> APROBADO. Solo si ninguno de los equipos está ya ocupado en esa
 *                              franja por otra reserva aprobada (RF40).
 *      rechazar   (admin)   -> RECHAZADO, con motivo.
 *   3. cancelar   (cliente o admin) desde PENDIENTE o APROBADO -> CANCELADO. Libera la franja.
 *
 * Regla de solapamiento (la parte más importante de este archivo):
 *   dos franjas del mismo día se pisan si   inicioA < finB   Y   finA > inicioB.
 *   Si una termina justo cuando la otra empieza (12:00 y 12:00) NO se pisan.
 *
 * Garantía: es imposible dejar aprobadas dos reservas superpuestas del mismo equipo, ni siquiera
 * si dos administradores aprueban al mismo tiempo. Se logra en aprobar() bloqueando las filas
 * de los equipos (SELECT ... FOR UPDATE) antes de revisar los conflictos.
 *
 * Alcance actual: solicitar, aprobar, rechazar, cancelar, listar y calendario.
 * Pendiente para más adelante: modificar una reserva (RF41), historial al finalizar (RF45 a RF47).
 */
require_once __DIR__ . '/../../Config/Conexion.php';
require_once __DIR__ . '/../../Config/Estados.php';

class Modelo_Reservas {
    /** @var PDO */
    private $conexion;

    /** Estados de una reserva que ocupan la franja de un equipo. */
    private const ESTADOS_QUE_OCUPAN = ['APROBADO', 'ACTIVO'];

    public function __construct(?PDO $conexion = null) {
        $this->conexion = $conexion ?: Conexion::conectar();
    }

    /* ==============================================================
       CONSULTAS
       ============================================================== */

    /** Equipos que se pueden reservar: unidades con serie que no están en mantenimiento ni de baja. */
    public function equiposReservables() {
        $st = $this->conexion->prepare(
            "SELECT id_material, id_serie, nombre, categoria, estado
               FROM material
              WHERE tipo_control = 'SERIE'
                AND estado NOT IN (?, ?)
              ORDER BY nombre, id_serie");
        $st->execute([EstadoMaterial::EN_MANTENIMIENTO, EstadoMaterial::DADO_DE_BAJA]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calendario de disponibilidad (RF39): franjas ocupadas por reservas aprobadas.
     * No muestra datos del cliente, solo qué equipo está ocupado y cuándo.
     * Sin fechas, devuelve los próximos 7 días.
     */
    public function calendario($desde = null, $hasta = null, $idMaterial = null) {
        $tz = new DateTimeZone('America/Montevideo');
        $hoy = new DateTimeImmutable('today', $tz);
        $desde = $this->fechaValida($desde ?: $hoy->format('Y-m-d'));
        $hasta = $this->fechaValida($hasta ?: $hoy->modify('+7 days')->format('Y-m-d'));
        if ($hasta < $desde) {
            throw new ErrorNegocio('La fecha «hasta» no puede ser anterior a «desde».');
        }

        $sql = "SELECT r.fecha, r.hora_inicio, r.hora_fin, s.id_solicitud, s.estado,
                       m.id_material, m.id_serie, m.nombre
                  FROM reserva r
                  JOIN solicitud s        ON s.id_solicitud  = r.id_solicitud
                  JOIN reserva_material rm ON rm.id_solicitud = r.id_solicitud
                  JOIN material m         ON m.id_material   = rm.id_material
                 WHERE s.estado IN ('APROBADO', 'ACTIVO')
                   AND r.fecha BETWEEN ? AND ?";
        $params = [$desde, $hasta];
        if ($idMaterial) {
            $sql .= " AND rm.id_material = ?";
            $params[] = (int)$idMaterial;
        }
        $sql .= " ORDER BY r.fecha, r.hora_inicio, m.id_serie";
        $st = $this->conexion->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista reservas. Filtros opcionales (RF42 y RF43): estado, ci_cliente, fecha.
     * A las PENDIENTES les agrega <conflictos>: ids de reservas ya aprobadas que se superponen
     * con ellas, para que el administrador sepa que no se pueden aprobar (RF40).
     */
    public function listar(array $filtros = []) {
        $sql = "SELECT s.id_solicitud, s.estado, s.fecha_solicitud, s.fecha_validacion, s.motivo_rechazo,
                       s.ci_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente, s.ci_administrador,
                       r.fecha, r.hora_inicio, r.hora_fin,
                       GROUP_CONCAT(COALESCE(m.id_serie, m.nombre) ORDER BY m.id_serie SEPARATOR ', ') AS equipos
                  FROM solicitud s
                  JOIN reserva r           ON r.id_solicitud  = s.id_solicitud
                  JOIN usuario u           ON u.ci            = s.ci_cliente
                  JOIN reserva_material rm ON rm.id_solicitud = s.id_solicitud
                  JOIN material m          ON m.id_material   = rm.id_material
                 WHERE 1 = 1";
        $params = [];
        if (!empty($filtros['estado'])) {
            $sql .= " AND s.estado = ?";
            $params[] = strtoupper(trim($filtros['estado']));
        }
        if (!empty($filtros['ci_cliente'])) {
            $sql .= " AND s.ci_cliente = ?";
            $params[] = trim($filtros['ci_cliente']);
        }
        if (!empty($filtros['fecha'])) {
            $sql .= " AND r.fecha = ?";
            $params[] = $this->fechaValida($filtros['fecha']);
        }
        $sql .= " GROUP BY s.id_solicitud, s.estado, s.fecha_solicitud, s.fecha_validacion, s.motivo_rechazo,
                           s.ci_cliente, u.nombre, u.apellido, s.ci_administrador, r.fecha, r.hora_inicio, r.hora_fin
                  ORDER BY r.fecha DESC, r.hora_inicio DESC, s.id_solicitud DESC";
        $st = $this->conexion->prepare($sql);
        $st->execute($params);
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as &$f) {
            $f['conflictos'] = [];
            if ($f['estado'] === EstadoSolicitud::PENDIENTE) {
                $choques = $this->conflictos($this->idsEquipos($f['id_solicitud']), $f['fecha'],
                                             $f['hora_inicio'], $f['hora_fin'], (int)$f['id_solicitud']);
                $f['conflictos'] = array_values(array_unique(array_column($choques, 'id_solicitud')));
            }
            $f['en_conflicto'] = count($f['conflictos']) > 0;
        }
        return $filas;
    }

    /* ==============================================================
       ACCIONES
       ============================================================== */

    /**
     * Cliente: pide reservar uno o más equipos para una fecha y franja. Queda PENDIENTE.
     * Devuelve el id de la solicitud.
     * @param int[] $idsEquipos id_material de cada equipo que se quiere reservar
     */
    public function solicitar($ciCliente, array $idsEquipos, $fecha, $horaInicio, $horaFin) {
        $ids = $this->normalizarIds($idsEquipos);
        if (!$ids) {
            throw new ErrorNegocio('Elegí al menos un equipo para reservar.');
        }
        [$fecha, $ini, $fin] = $this->normalizarFranja($fecha, $horaInicio, $horaFin);

        $errores = $this->erroresDeEquipos($ids);
        if (!$errores) {
            foreach ($this->conflictos($ids, $fecha, $ini, $fin) as $c) {
                $errores[] = $this->textoConflicto($c);
            }
        }
        if ($errores) {
            throw new ErrorNegocio('No se pudo registrar la reserva.', $errores);
        }

        $this->conexion->beginTransaction();
        try {
            $this->conexion->prepare(
                "INSERT INTO solicitud (estado, fecha_solicitud, ci_cliente) VALUES ('PENDIENTE', NOW(), ?)")
                ->execute([$ciCliente]);
            $id = (int)$this->conexion->lastInsertId();

            $this->conexion->prepare(
                "INSERT INTO reserva (id_solicitud, fecha, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)")
                ->execute([$id, $fecha, $ini, $fin]);

            $st = $this->conexion->prepare(
                "INSERT INTO reserva_material (id_solicitud, id_material, cantidad) VALUES (?, ?, 1)");
            foreach ($ids as $idMaterial) {
                $st->execute([$id, $idMaterial]);
            }
            $this->conexion->commit();
            return $id;
        } catch (Exception $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    /** PENDIENTE -> APROBADO, solo si ningún equipo está ocupado en esa franja (RF38 y RF40). */
    public function aprobar($idSolicitud, $ciAdmin) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin) {
            $r = $this->bloquearReserva($idSolicitud);
            EstadoSolicitud::destino($r['estado'], EstadoSolicitud::APROBAR);

            $ids = $this->idsEquipos($idSolicitud);

            // Bloqueo: nadie más puede aprobar una reserva de estos equipos hasta que terminemos.
            // Se piden siempre en el mismo orden (ORDER BY) para que dos aprobaciones no se traben entre sí.
            $in = implode(',', array_fill(0, count($ids), '?'));
            $st = $this->conexion->prepare(
                "SELECT id_material FROM material WHERE id_material IN ($in) ORDER BY id_material FOR UPDATE");
            $st->execute($ids);

            // Con el bloqueo tomado, se revisa el estado de los equipos y los choques de horario.
            $errores = $this->erroresDeEquipos($ids);
            foreach ($this->conflictos($ids, $r['fecha'], $r['hora_inicio'], $r['hora_fin'], (int)$idSolicitud) as $c) {
                $errores[] = $this->textoConflicto($c);
            }
            if ($errores) {
                throw new ErrorNegocio('No se puede aprobar la reserva.', $errores);
            }

            $this->conexion->prepare(
                "UPDATE solicitud SET estado = 'APROBADO', fecha_validacion = NOW(), ci_administrador = ?
                  WHERE id_solicitud = ?")
                ->execute([$ciAdmin, $idSolicitud]);
        });
    }

    /** PENDIENTE -> RECHAZADO, con motivo obligatorio. */
    public function rechazar($idSolicitud, $ciAdmin, $motivo) {
        if (trim((string)$motivo) === '') {
            throw new ErrorNegocio('Indicá el motivo del rechazo.');
        }
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin, $motivo) {
            $r = $this->bloquearReserva($idSolicitud);
            EstadoSolicitud::destino($r['estado'], EstadoSolicitud::RECHAZAR);
            $this->conexion->prepare(
                "UPDATE solicitud SET estado = 'RECHAZADO', fecha_validacion = NOW(), motivo_rechazo = ?, ci_administrador = ?
                  WHERE id_solicitud = ?")
                ->execute([trim($motivo), $ciAdmin, $idSolicitud]);
        });
    }

    /** PENDIENTE | APROBADO -> CANCELADO. El cliente solo cancela las suyas. Libera la franja. */
    public function cancelar($idSolicitud, $ciUsuario, $esAdmin) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciUsuario, $esAdmin) {
            $r = $this->bloquearReserva($idSolicitud);
            if (!$esAdmin && $r['ci_cliente'] !== (string)$ciUsuario) {
                throw new ErrorNegocio('Solo podés cancelar tus propias reservas.');
            }
            EstadoSolicitud::destino($r['estado'], EstadoSolicitud::CANCELAR);
            $this->conexion->prepare("UPDATE solicitud SET estado = 'CANCELADO' WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);
        });
    }

    /* ==============================================================
       AUXILIARES (privadas)
       ============================================================== */

    /**
     * Reservas aprobadas (o activas) que pisan la franja pedida en alguno de los equipos.
     * Aquí vive la regla: hora_inicio_existente < fin_nuevo  Y  hora_fin_existente > inicio_nuevo.
     * @param int $excluirId reserva a ignorar (la que se está aprobando, para que no choque consigo misma)
     */
    private function conflictos(array $idsEquipos, $fecha, $ini, $fin, $excluirId = 0) {
        if (!$idsEquipos) {
            return [];
        }
        $in = implode(',', array_fill(0, count($idsEquipos), '?'));
        $ocupan = "'" . implode("','", self::ESTADOS_QUE_OCUPAN) . "'";
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud, r.hora_inicio, r.hora_fin, r.fecha, m.id_material, m.id_serie, m.nombre
               FROM reserva r
               JOIN solicitud s         ON s.id_solicitud  = r.id_solicitud
               JOIN reserva_material rm ON rm.id_solicitud = r.id_solicitud
               JOIN material m          ON m.id_material   = rm.id_material
              WHERE rm.id_material IN ($in)
                AND r.fecha = ?
                AND s.estado IN ($ocupan)
                AND s.id_solicitud <> ?
                AND r.hora_inicio < ?
                AND r.hora_fin    > ?");
        $st->execute(array_merge($idsEquipos, [$fecha, (int)$excluirId, $fin, $ini]));
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function textoConflicto(array $c) {
        $equipo = $c['id_serie'] ?: $c['nombre'];
        return sprintf('%s ya está reservado el %s de %s a %s (reserva #%d).',
            $equipo, $c['fecha'], substr($c['hora_inicio'], 0, 5), substr($c['hora_fin'], 0, 5), $c['id_solicitud']);
    }

    /** Comprueba que cada equipo exista, tenga serie y no esté en mantenimiento ni dado de baja. */
    private function erroresDeEquipos(array $ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->conexion->prepare(
            "SELECT id_material, id_serie, nombre, tipo_control, estado FROM material WHERE id_material IN ($in)");
        $st->execute($ids);
        $encontrados = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $encontrados[(int)$m['id_material']] = $m;
        }

        $errores = [];
        foreach ($ids as $id) {
            if (!isset($encontrados[$id])) {
                $errores[] = "El equipo #{$id} no existe.";
                continue;
            }
            $m = $encontrados[$id];
            $nombre = $m['id_serie'] ?: $m['nombre'];
            if ($m['tipo_control'] !== 'SERIE') {
                $errores[] = "«{$m['nombre']}» no se reserva: solo se reservan equipos con número de serie.";
            } elseif ($m['estado'] === EstadoMaterial::DADO_DE_BAJA) {
                $errores[] = "{$nombre} está dado de baja.";
            } elseif ($m['estado'] === EstadoMaterial::EN_MANTENIMIENTO) {
                $errores[] = "{$nombre} está en mantenimiento.";
            }
        }
        return $errores;
    }

    private function idsEquipos($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT id_material FROM reserva_material WHERE id_solicitud = ? ORDER BY id_material");
        $st->execute([$idSolicitud]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Trae la reserva y bloquea su fila (FOR UPDATE) para que nadie la cambie a la vez. */
    private function bloquearReserva($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud, s.estado, s.ci_cliente, r.fecha, r.hora_inicio, r.hora_fin
               FROM solicitud s JOIN reserva r ON r.id_solicitud = s.id_solicitud
              WHERE s.id_solicitud = ? FOR UPDATE");
        $st->execute([$idSolicitud]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            throw new ErrorNegocio("No existe la reserva #{$idSolicitud}.");
        }
        return $r;
    }

    private function normalizarIds(array $ids) {
        $limpios = [];
        foreach ($ids as $id) {
            $n = (int)$id;
            if ($n > 0) {
                $limpios[$n] = $n;
            }
        }
        return array_values($limpios);
    }

    /** Valida fecha y horas, y devuelve [fecha 'Y-m-d', inicio 'HH:MM:SS', fin 'HH:MM:SS']. */
    private function normalizarFranja($fecha, $horaInicio, $horaFin) {
        $fecha = $this->fechaValida($fecha);
        $ini = $this->horaValida($horaInicio, 'de inicio');
        $fin = $this->horaValida($horaFin, 'de fin');
        if ($fin <= $ini) {
            throw new ErrorNegocio('La hora de fin tiene que ser posterior a la de inicio.');
        }
        $tz = new DateTimeZone('America/Montevideo');
        if (new DateTimeImmutable("$fecha $ini", $tz) < new DateTimeImmutable('now', $tz)) {
            throw new ErrorNegocio('La reserva no puede empezar en el pasado.');
        }
        return [$fecha, $ini, $fin];
    }

    private function fechaValida($fecha) {
        $fecha = trim((string)$fecha);
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            throw new ErrorNegocio('La fecha no es válida. Usá el formato AAAA-MM-DD.');
        }
        return $fecha;
    }

    private function horaValida($hora, $nombre) {
        $hora = trim((string)$hora);
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $hora)) {
            throw new ErrorNegocio("La hora {$nombre} no es válida. Usá el formato HH:MM.");
        }
        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }

    private function enTransaccion(callable $fn) {
        // READ COMMITTED: cada consulta ve lo último confirmado. Con el nivel por defecto (REPEATABLE READ)
        // la primera lectura común fija una <foto> de la base; si se toma antes de esperar el bloqueo,
        // la revisión de choques no vería la aprobación que otro administrador acaba de confirmar.
        $this->conexion->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->conexion->beginTransaction();
        try {
            $r = $fn();
            $this->conexion->commit();
            return $r === null ? true : $r;
        } catch (Exception $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }
}
