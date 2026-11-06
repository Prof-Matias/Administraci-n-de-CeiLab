<?php

require_once __DIR__ . '/../../Config/Conexion.php';
require_once __DIR__ . '/../../Config/Estados.php';
require_once __DIR__ . '/../../Config/Disponibilidad.php';

class Modelo_Reservas {
    private $conexion;
    private $disp;

    public function __construct(?PDO $conexion = null) {
        $this->conexion = $conexion ?: Conexion::conectar();
        $this->disp     = new Disponibilidad($this->conexion);
    }

    // Consultas

    // Materiales que se pueden reservar. Si llegan fecha y horario, agrega el stock de esa franja.
    public function catalogo($fecha = null, $horaInicio = null, $horaFin = null) {
        $franja = null;
        if ($fecha !== null && $fecha !== '' && $horaInicio && $horaFin) {
            [$f, $ini, $fin] = $this->normalizarFranja($fecha, $horaInicio, $horaFin, false);
            $franja = [Disponibilidad::momento($f, $ini), Disponibilidad::momento($f, $fin)];
        }
        $tipos = $this->conexion->query(
            "SELECT nombre, MIN(categoria) AS categoria, tipo_control
               FROM material WHERE estado <> 'DADO_DE_BAJA'
              GROUP BY nombre, tipo_control
              ORDER BY MIN(categoria), nombre")->fetchAll(PDO::FETCH_ASSOC);

        $ahora = Disponibilidad::ahora();
        $salida = [];
        foreach ($tipos as $t) {
            if ($franja) {
                $ev = $this->disp->evaluar($t['nombre'], $franja[0], $franja[1], 0, $ahora);
                $salida[] = $ev;
            } else {
                $cap = $this->disp->capacidad($t['nombre']);
                $salida[] = ['nombre' => $t['nombre'], 'categoria' => $t['categoria'], 'tipo_control' => $t['tipo_control'],
                             'capacidad' => $cap['capacidad'], 'en_mantenimiento' => $cap['en_mantenimiento']];
            }
        }
        return $salida;
    }

    // Calendario de ocupación, sin datos de los clientes. Sin fechas muestra los próximos 7 días.
    public function calendario($desde = null, $hasta = null, $nombre = null) {
        $hoy   = new DateTimeImmutable('today', new DateTimeZone('America/Montevideo'));
        $desde = $this->fechaValida($desde ?: $hoy->format('Y-m-d'));
        $hasta = $this->fechaValida($hasta ?: $hoy->modify('+7 days')->format('Y-m-d'));
        if ($hasta < $desde) {
            throw new ErrorNegocio('La fecha «hasta» no puede ser anterior a «desde».');
        }

        $sql = "SELECT r.fecha, r.hora_inicio, r.hora_fin, s.id_solicitud, s.estado, rp.nombre_material AS nombre, rp.cantidad
                  FROM reserva r
                  JOIN solicitud s       ON s.id_solicitud = r.id_solicitud AND s.estado IN ('APROBADO', 'ACTIVO')
                  JOIN reserva_pedido rp ON rp.id_solicitud = r.id_solicitud
                 WHERE r.fecha BETWEEN ? AND ?";
        $params = [$desde, $hasta];
        if ($nombre !== null && $nombre !== '') {
            $sql .= " AND rp.nombre_material = ?";
            $params[] = $nombre;
        }
        $sql .= " ORDER BY r.fecha, r.hora_inicio, rp.nombre_material";
        $st = $this->conexion->prepare($sql);
        $st->execute($params);
        $reservas = $st->fetchAll(PDO::FETCH_ASSOC);

        // Préstamos que ocupan stock en ese rango
        $ahoraTxt = Disponibilidad::ahora()->format('Y-m-d H:i:s');
        $rangoIni = $desde . ' 00:00:00';
        $rangoFin = $hasta . ' 23:59:59';
        $nombres = $nombre !== null && $nombre !== ''
            ? [$nombre]
            : $this->conexion->query("SELECT DISTINCT nombre FROM material WHERE estado <> 'DADO_DE_BAJA' ORDER BY nombre")
                             ->fetchAll(PDO::FETCH_COLUMN);
        $prestamos = [];
        foreach ($nombres as $n) {
            foreach ($this->disp->ventanasPrestamo($n, $ahoraTxt) as $v) {
                if ($v['inicio'] <= $rangoFin && $v['fin'] >= $rangoIni) {
                    $prestamos[] = ['nombre' => $n, 'cantidad' => $v['cantidad'], 'estado' => $v['estado'],
                                    'desde' => $v['inicio'], 'hasta' => $v['fin']];
                }
            }
        }
        return ['reservas' => $reservas, 'prestamos' => $prestamos];
    }

    // Lista de reservas con sus materiales. Filtros: estado, ci_cliente y fecha.
    // Las PENDIENTES avisan si hoy no alcanzaría el stock para aprobarlas.
    public function listar(array $filtros = []) {
        $sql = "SELECT s.id_solicitud, s.estado, s.fecha_solicitud, s.fecha_validacion, s.motivo_rechazo,
                       s.ci_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente, s.ci_administrador,
                       r.fecha, r.hora_inicio, r.hora_fin
                  FROM solicitud s
                  JOIN reserva r ON r.id_solicitud = s.id_solicitud
                  JOIN usuario u ON u.ci = s.ci_cliente
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
        $sql .= " ORDER BY r.fecha DESC, r.hora_inicio DESC, s.id_solicitud DESC";
        $st = $this->conexion->prepare($sql);
        $st->execute($params);
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$filas) return [];

        $ids = array_map(function ($f) { return (int)$f['id_solicitud']; }, $filas);
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $st = $this->conexion->prepare(
            "SELECT id_solicitud, nombre_material AS nombre, cantidad FROM reserva_pedido
              WHERE id_solicitud IN ($in) ORDER BY nombre_material");
        $st->execute($ids);
        $items = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $i) {
            $items[$i['id_solicitud']][] = ['nombre' => $i['nombre'], 'cantidad' => (int)$i['cantidad']];
        }

        $st = $this->conexion->prepare(
            "SELECT rm.id_solicitud, m.id_material, m.id_serie, m.nombre, rm.cantidad
               FROM reserva_material rm JOIN material m ON m.id_material = rm.id_material
              WHERE rm.id_solicitud IN ($in) ORDER BY m.nombre, m.id_material");
        $st->execute($ids);
        $asignadas = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $asignadas[$a['id_solicitud']][] = ['id_material' => (int)$a['id_material'], 'id_serie' => $a['id_serie'],
                                                'nombre' => $a['nombre'], 'cantidad' => (int)$a['cantidad']];
        }

        $ahora = Disponibilidad::ahora();
        foreach ($filas as &$f) {
            $id = (int)$f['id_solicitud'];
            $f['items']     = $items[$id] ?? [];
            $f['asignadas'] = $asignadas[$id] ?? [];
            $f['resumen']   = implode(', ', array_map(function ($i) { return $i['nombre'] . ' ×' . $i['cantidad']; }, $f['items']));
            $f['sin_stock'] = [];
            if ($f['estado'] === EstadoSolicitud::PENDIENTE) {
                $desde = Disponibilidad::momento($f['fecha'], $f['hora_inicio']);
                $hasta = Disponibilidad::momento($f['fecha'], $f['hora_fin']);
                foreach ($f['items'] as $i) {
                    $ev = $this->disp->evaluar($i['nombre'], $desde, $hasta, $id, $ahora);
                    if ($ev['libres'] < $i['cantidad']) {
                        $f['sin_stock'][] = ['nombre' => $i['nombre'], 'pedidas' => $i['cantidad'], 'libres' => $ev['libres']];
                    }
                }
            }
            $f['en_conflicto'] = count($f['sin_stock']) > 0;
        }
        unset($f);
        return $filas;
    }

    // Reservas finalizadas (historial). Filtros: ci_cliente y fecha.
    public function historial(array $filtros = [], $limite = 300) {
        $sql = "SELECT h.id_solicitud_origen AS id_solicitud, h.fecha_archivo, h.estado, h.fecha, h.hora_inicio, h.hora_fin,
                       h.detalle, h.ci_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente, h.ci_administrador
                  FROM historial_reserva h LEFT JOIN usuario u ON u.ci = h.ci_cliente WHERE 1 = 1";
        $params = [];
        if (!empty($filtros['ci_cliente'])) {
            $sql .= " AND h.ci_cliente = ?";
            $params[] = trim($filtros['ci_cliente']);
        }
        if (!empty($filtros['fecha'])) {
            $sql .= " AND h.fecha = ?";
            $params[] = $this->fechaValida($filtros['fecha']);
        }
        $limite = max(1, min(1000, (int)$limite));
        $sql .= " ORDER BY h.fecha DESC, h.hora_inicio DESC, h.id_historial DESC LIMIT $limite";
        $st = $this->conexion->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Solicitar

    // Pide materiales para una fecha y un horario. La usan el cliente y el administrador.
    // Si no alcanza el stock lanza ErrorDisponibilidad (SIN_STOCK o STOCK_PARCIAL), salvo que
    // se acepte reservar una parte o anotar el resto en espera.
    // Cuando el estado es APROBADO (la hace el administrador) se aprueba en el momento.
    public function solicitarReserva($fecha, $horaInicio, $horaFin, $items, $ciCliente = null, $estado = 'PENDIENTE',
                                     $aceptarParcial = false, $anotarEspera = false, $ciAdmin = null) {
        $porAdmin = ($estado === 'APROBADO');
        $ciCliente = trim((string)($ciCliente ?: ($_SESSION['CI'] ?? '')));
        $items = is_array($items) ? $items : [];
        if ($porAdmin) {
            $this->verificarTitular($ciCliente);
        }
        $uso = $this->usoSinDevolver($ciCliente);
        if ($uso && $porAdmin) {
            throw new ErrorNegocio($uso['tipo'] === 'RESERVA'
                ? "Esa persona tiene la reserva #{$uso['id_solicitud']} en uso y todavía sin devolver. Finalizala antes de hacerle otra."
                : "Esa persona tiene el préstamo #{$uso['id_solicitud']} entregado y todavía sin devolver. Registrá la devolución antes de hacerle una reserva.");
        }
        if ($uso) {
            throw new ErrorNegocio($uso['tipo'] === 'RESERVA'
                ? "Tenés la reserva #{$uso['id_solicitud']} en uso y todavía sin devolver. No podés hacer otra reserva hasta que se finalice."
                : "Tenés el préstamo #{$uso['id_solicitud']} entregado y todavía sin devolver. No podés hacer una reserva hasta que lo devuelvas.");
        }
        [$f, $ini, $fin] = $this->normalizarFranja($fecha, $horaInicio, $horaFin);
        $pedido = $this->normalizarItems($items);
        $desde  = Disponibilidad::momento($f, $ini);
        $hasta  = Disponibilidad::momento($f, $fin);
        $ahora  = Disponibilidad::ahora();
        if ($anotarEspera) $aceptarParcial = true;

        $errores = [];
        $detalle = [];      // por producto: lo que se pide y lo que hay
        $plan    = [];      // nombre => ['pedidas', 'otorga', 'falta']
        foreach ($pedido as $nombre => $cantidad) {
            $ev = $this->disp->evaluar($nombre, $desde, $hasta, 0, $ahora);
            if (!$ev['existe']) {
                $errores[] = "«{$nombre}» no existe en el inventario.";
                continue;
            }
            $otorga = min($ev['libres'], $cantidad);
            $falta  = $cantidad - $otorga;
            $plan[$nombre] = ['pedidas' => $cantidad, 'otorga' => $otorga, 'falta' => $falta];
            $detalle[] = [
                'nombre' => $nombre, 'solicitado' => $cantidad, 'libres' => $ev['libres'], 'faltan' => $falta,
                'capacidad' => $ev['capacidad'], 'en_mantenimiento' => $ev['en_mantenimiento'],
                'reservadas' => $ev['reservadas'], 'prestadas' => $ev['prestadas'],
                'proximos' => $ev['proximos'],
                'completo_desde' => $falta > 0 ? Disponibilidad::completoDesde($ev['proximos'], $falta) : null,
            ];
        }
        if ($errores) {
            throw new ErrorNegocio('No se pudo registrar la reserva.', $errores);
        }
        $info = ['franja' => ['fecha' => $f, 'hora_inicio' => substr($ini, 0, 5), 'hora_fin' => substr($fin, 0, 5)], 'items' => $detalle];

        $hayAlgo = array_sum(array_column($plan, 'otorga')) > 0;
        $hayFalta = array_sum(array_column($plan, 'falta')) > 0;

        if (!$hayAlgo && !$anotarEspera) {
            throw new ErrorDisponibilidad(ErrorDisponibilidad::SIN_STOCK,
                'CeiLab no se encuentra con stock disponible para esa franja.', $info);
        }
        if ($hayAlgo && $hayFalta && !$aceptarParcial) {
            throw new ErrorDisponibilidad(ErrorDisponibilidad::STOCK_PARCIAL,
                'No hay stock suficiente para todo lo pedido. Podés reservar lo que está disponible y anotarte en lista de espera por el resto.',
                $info);
        }

        // Se guarda la reserva con lo que hay y, si se pidió, la espera con lo que falta
        $resultado = $this->enTransaccion(function () use ($ciCliente, $f, $ini, $fin, $plan, $anotarEspera, $ahora) {
            $id = null;
            $conReserva = [];
            foreach ($plan as $nombre => $p) {
                if ($p['otorga'] > 0) $conReserva[$nombre] = $p['otorga'];
            }
            if ($conReserva) {
                $id = $this->insertarSolicitud($ciCliente, $f, $ini, $fin, $conReserva);
            }
            $espera = [];
            if ($anotarEspera) {
                foreach ($plan as $nombre => $p) {
                    if ($p['falta'] > 0) {
                        $espera[] = $this->insertarEspera($ciCliente, $nombre, $p['falta'], $f, $ini, $fin, $ahora, $id);
                    }
                }
            }
            return ['id_solicitud' => $id, 'espera' => $espera];
        });

        $resultado['items'] = array_map(function ($nombre, $p) use ($anotarEspera) {
            return ['nombre' => $nombre, 'solicitado' => $p['pedidas'], 'reservado' => $p['otorga'],
                    'en_espera' => $anotarEspera ? $p['falta'] : 0, 'sin_cubrir' => $anotarEspera ? 0 : $p['falta']];
        }, array_keys($plan), array_values($plan));
        $resultado['completo'] = !$hayFalta;

        // Si la hizo el administrador, se aprueba en el momento (asigna las unidades)
        $resultado['aprobada'] = false;
        if ($porAdmin && $resultado['id_solicitud']) {
            $ciQueAprueba = $ciAdmin ?: ($_SESSION['CI'] ?? $ciCliente);
            try {
                $this->aprobar($resultado['id_solicitud'], $ciQueAprueba);
                $resultado['aprobada'] = true;
            } catch (ErrorNegocio $e) {
                // Otro administrador ocupó el stock en el medio: queda pendiente para revisarla
                $resultado['aviso'] = 'La reserva quedó pendiente: ' . $e->getMessage();
            }
        }
        return $resultado;
    }

    // La persona tiene que existir y tener la cuenta activa
    private function verificarTitular($ci) {
        $st = $this->conexion->prepare("SELECT estado_cuenta FROM usuario WHERE ci = ?");
        $st->execute([$ci]);
        $estado = $st->fetchColumn();
        if ($estado === false) {
            throw new ErrorNegocio("No hay ningún usuario con la cédula {$ci}.");
        }
        if ($estado !== 'ACTIVA') {
            throw new ErrorNegocio("La cuenta {$ci} está inactiva: no se le pueden hacer reservas.");
        }
    }

    // Cambios de estado

    // Aprobar: se revisa el stock de cada línea en esa franja y se asignan las unidades.
    // Si no alcanza, no se aprueba.
    public function aprobar($idSolicitud, $ciAdmin) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin) {
            $r = $this->bloquearReserva($idSolicitud);
            EstadoSolicitud::destino($r['estado'], EstadoSolicitud::APROBAR);
            $items = $this->itemsDe($idSolicitud);
            $this->bloquearTipos(array_column($items, 'nombre'));

            $desde = Disponibilidad::momento($r['fecha'], $r['hora_inicio']);
            $hasta = Disponibilidad::momento($r['fecha'], $r['hora_fin']);
            $ahora = Disponibilidad::ahora();
            if ($hasta <= $ahora->format('Y-m-d H:i:s')) {
                throw new ErrorNegocio('No se puede aprobar: la franja de la reserva ya terminó.');
            }
            $this->verificarStock($items, $desde, $hasta, $idSolicitud, $ahora, 'No se puede aprobar la reserva.');
            $this->asignarUnidades($idSolicitud, $items, $desde, $hasta);

            $this->conexion->prepare(
                "UPDATE solicitud SET estado = 'APROBADO', fecha_validacion = NOW(), ci_administrador = ?
                  WHERE id_solicitud = ?")
                ->execute([$ciAdmin, $idSolicitud]);
        });
    }

    // Rechazar: el motivo es obligatorio
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

    // Cancelar si está PENDIENTE o APROBADO (el cliente solo las suyas). Libera stock, así que atiende la espera.
    public function cancelar($idSolicitud, $ciUsuario, $esAdmin) {
        $this->enTransaccion(function () use ($idSolicitud, $ciUsuario, $esAdmin) {
            $r = $this->bloquearReserva($idSolicitud);
            if (!$esAdmin && $r['ci_cliente'] !== $ciUsuario) {
                throw new ErrorNegocio('Solo podés cancelar tus propias reservas.');
            }
            EstadoSolicitud::destino($r['estado'], EstadoSolicitud::CANCELAR);
            $this->conexion->prepare("UPDATE solicitud SET estado = 'CANCELADO' WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);
        });
        $this->procesarEspera();
        return true;
    }

    // Modifica una reserva PENDIENTE o APROBADA. El admin puede cualquiera; el cliente solo las suyas
    // y si están PENDIENTES. Se vuelve a revisar el stock sin contar la propia reserva.
    public function modificar($idSolicitud, $ciUsuario, $esAdmin, array $cambios) {
        $this->enTransaccion(function () use ($idSolicitud, $ciUsuario, $esAdmin, $cambios) {
            $r = $this->bloquearReserva($idSolicitud);
            if (!$esAdmin && $r['ci_cliente'] !== $ciUsuario) {
                throw new ErrorNegocio('Solo podés modificar tus propias reservas.');
            }
            if (!in_array($r['estado'], [EstadoSolicitud::PENDIENTE, EstadoSolicitud::APROBADO], true)) {
                throw new ErrorNegocio('Solo se pueden modificar reservas pendientes o aprobadas.');
            }
            if (!$esAdmin && $r['estado'] !== EstadoSolicitud::PENDIENTE) {
                throw new ErrorNegocio('Tu reserva ya fue aprobada: pedile al administrador que la modifique o cancelala y volvé a solicitarla.');
            }

            $itemsViejos = $this->itemsDe($idSolicitud);
            $items = isset($cambios['items'])
                ? $this->itemsDeMapa($this->normalizarItems($cambios['items']))
                : $itemsViejos;
            [$f, $ini, $fin] = $this->normalizarFranja(
                $cambios['fecha'] ?? $r['fecha'],
                $cambios['hora_inicio'] ?? $r['hora_inicio'],
                $cambios['hora_fin'] ?? $r['hora_fin']);

            $this->bloquearTipos(array_merge(array_column($items, 'nombre'), array_column($itemsViejos, 'nombre')));
            $desde = Disponibilidad::momento($f, $ini);
            $hasta = Disponibilidad::momento($f, $fin);
            $this->verificarStock($items, $desde, $hasta, $idSolicitud, Disponibilidad::ahora(), 'No se puede modificar la reserva.');

            $this->conexion->prepare("UPDATE reserva SET fecha = ?, hora_inicio = ?, hora_fin = ? WHERE id_solicitud = ?")
                           ->execute([$f, $ini, $fin, $idSolicitud]);
            $this->conexion->prepare("DELETE FROM reserva_pedido WHERE id_solicitud = ?")->execute([$idSolicitud]);
            $ins = $this->conexion->prepare("INSERT INTO reserva_pedido (id_solicitud, nombre_material, cantidad) VALUES (?, ?, ?)");
            foreach ($items as $i) {
                $ins->execute([$idSolicitud, $i['nombre'], $i['cantidad']]);
            }
            $this->conexion->prepare("DELETE FROM reserva_material WHERE id_solicitud = ?")->execute([$idSolicitud]);
            if ($r['estado'] === EstadoSolicitud::APROBADO) {
                $this->asignarUnidades($idSolicitud, $items, $desde, $hasta);
            }
        });
        $this->procesarEspera();   // si se achicó o se movió, puede liberar stock
        return true;
    }

    // Iniciar: pasa a ACTIVO cuando se empieza a usar. Solo desde el día de la reserva.
    public function iniciar($idSolicitud, $ciAdmin) {
        return $this->enTransaccion(function () use ($idSolicitud) {
            $r = $this->bloquearReserva($idSolicitud);
            EstadoSolicitud::destino($r['estado'], EstadoSolicitud::ENTREGAR);
            if ($r['fecha'] > Disponibilidad::ahora()->format('Y-m-d')) {
                throw new ErrorNegocio('La reserva es para el ' . $r['fecha'] . ': todavía no se puede iniciar el uso.');
            }
            $this->conexion->prepare("UPDATE solicitud SET estado = 'ACTIVO' WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);
        });
    }

    // Finalizar: se copia en historial_reserva y libera el stock, así que atiende la espera.
    public function finalizar($idSolicitud, $ciAdmin) {
        $this->enTransaccion(function () use ($idSolicitud, $ciAdmin) {
            $r = $this->bloquearReserva($idSolicitud);
            $estado = $r['estado'];
            if ($estado === EstadoSolicitud::APROBADO) {
                if ($r['fecha'] > Disponibilidad::ahora()->format('Y-m-d')) {
                    throw new ErrorNegocio('La reserva es para el ' . $r['fecha'] . ': todavía no se puede finalizar.');
                }
                $estado = EstadoSolicitud::destino($estado, EstadoSolicitud::ENTREGAR);   // primero pasa a en uso
            }
            EstadoSolicitud::destino($estado, EstadoSolicitud::FINALIZAR);

            $this->conexion->prepare("UPDATE solicitud SET estado = 'FINALIZADO' WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);

            $detalle = [];
            foreach ($this->itemsDe($idSolicitud) as $i) {
                $detalle[] = $i['nombre'] . ' ×' . $i['cantidad'];
            }
            $series = $this->conexion->prepare(
                "SELECT m.id_serie FROM reserva_material rm JOIN material m ON m.id_material = rm.id_material
                  WHERE rm.id_solicitud = ? AND m.id_serie IS NOT NULL ORDER BY m.id_serie");
            $series->execute([$idSolicitud]);
            $lista = $series->fetchAll(PDO::FETCH_COLUMN);
            $texto = implode(', ', $detalle) . ($lista ? ' (' . implode(', ', $lista) . ')' : '');

            $this->conexion->prepare(
                "INSERT INTO historial_reserva
                     (id_solicitud_origen, fecha_archivo, estado, fecha, hora_inicio, hora_fin, detalle,
                      fecha_solicitud, fecha_validacion, ci_cliente, ci_administrador)
                 SELECT s.id_solicitud, NOW(), 'FINALIZADO', r.fecha, r.hora_inicio, r.hora_fin, ?,
                        s.fecha_solicitud, s.fecha_validacion, s.ci_cliente, ?
                   FROM solicitud s JOIN reserva r ON r.id_solicitud = s.id_solicitud
                  WHERE s.id_solicitud = ?")
                ->execute([$texto, $ciAdmin, $idSolicitud]);
        });
        $this->procesarEspera();
        return true;
    }

    // Lista de espera

    // Filtros: ci_cliente y estado
    public function listarEspera(array $filtros = []) {
        $sql = "SELECT e.id_espera, e.ci_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente, e.nombre_material AS nombre,
                       e.cantidad, e.fecha, e.hora_inicio, e.hora_fin, e.estado, e.fecha_alta, e.fecha_atencion, e.id_solicitud_generada
                  FROM reserva_espera e JOIN usuario u ON u.ci = e.ci_cliente WHERE 1 = 1";
        $params = [];
        if (!empty($filtros['ci_cliente'])) {
            $sql .= " AND e.ci_cliente = ?";
            $params[] = trim($filtros['ci_cliente']);
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND e.estado = ?";
            $params[] = strtoupper(trim($filtros['estado']));
        }
        $sql .= " ORDER BY e.fecha DESC, e.hora_inicio DESC, e.id_espera DESC";
        $st = $this->conexion->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelarEspera($idEspera, $ciUsuario, $esAdmin) {
        return $this->enTransaccion(function () use ($idEspera, $ciUsuario, $esAdmin) {
            $st = $this->conexion->prepare("SELECT ci_cliente, estado FROM reserva_espera WHERE id_espera = ? FOR UPDATE");
            $st->execute([(int)$idEspera]);
            $e = $st->fetch(PDO::FETCH_ASSOC);
            if (!$e) throw new ErrorNegocio("No existe la espera #{$idEspera}.");
            if (!$esAdmin && $e['ci_cliente'] !== $ciUsuario) {
                throw new ErrorNegocio('Solo podés cancelar tus propias esperas.');
            }
            if ($e['estado'] !== 'ESPERANDO') {
                throw new ErrorNegocio('Esa espera ya no está activa (' . strtolower($e['estado']) . ').');
            }
            $this->conexion->prepare("UPDATE reserva_espera SET estado = 'CANCELADA' WHERE id_espera = ?")
                           ->execute([(int)$idEspera]);
        });
    }

    // Recorre la espera por orden de llegada. Si ya hay stock para todo lo que alguien esperaba,
    // se le crea una solicitud PENDIENTE y la espera queda ATENDIDA. Devuelve cuántas atendió.
    public function procesarEspera() {
        $ids = $this->conexion->query("SELECT id_espera FROM reserva_espera WHERE estado = 'ESPERANDO' ORDER BY id_espera")
                              ->fetchAll(PDO::FETCH_COLUMN);
        $atendidas = 0;
        foreach ($ids as $idEspera) {
            $ok = $this->enTransaccion(function () use ($idEspera) {
                $st = $this->conexion->prepare("SELECT * FROM reserva_espera WHERE id_espera = ? AND estado = 'ESPERANDO' FOR UPDATE");
                $st->execute([(int)$idEspera]);
                $e = $st->fetch(PDO::FETCH_ASSOC);
                if (!$e) return false;

                $ahora = Disponibilidad::ahora();
                $desde = Disponibilidad::momento($e['fecha'], $e['hora_inicio']);
                $hasta = Disponibilidad::momento($e['fecha'], $e['hora_fin']);
                if ($hasta <= $ahora->format('Y-m-d H:i:s')) {
                    $this->conexion->prepare("UPDATE reserva_espera SET estado = 'VENCIDA' WHERE id_espera = ?")
                                   ->execute([(int)$idEspera]);
                    return false;
                }
                // Si la reserva parcial que la acompaña sigue PENDIENTE, se espera a que se resuelva
                // para no prometer las mismas unidades dos veces
                if (!empty($e['id_solicitud_origen'])) {
                    $st = $this->conexion->prepare("SELECT estado FROM solicitud WHERE id_solicitud = ?");
                    $st->execute([(int)$e['id_solicitud_origen']]);
                    if ($st->fetchColumn() === 'PENDIENTE') return false;
                }

                $this->bloquearTipos([$e['nombre_material']]);
                $ev = $this->disp->evaluar($e['nombre_material'], $desde, $hasta, 0, $ahora);
                $libres = $ev['libres'] - $this->retenidoPorEspera($e['nombre_material'], $desde, $hasta);
                if ($libres < (int)$e['cantidad']) return false;

                $id = $this->insertarSolicitud($e['ci_cliente'], $e['fecha'], $e['hora_inicio'], $e['hora_fin'],
                                               [$e['nombre_material'] => (int)$e['cantidad']]);
                $this->conexion->prepare(
                    "UPDATE reserva_espera SET estado = 'ATENDIDA', fecha_atencion = NOW(), id_solicitud_generada = ?
                      WHERE id_espera = ?")->execute([$id, (int)$idEspera]);
                return true;
            });
            if ($ok) $atendidas++;
        }
        return $atendidas;
    }

    // Stock de la franja ya prometido a solicitudes PENDIENTES que salieron de la espera
    private function retenidoPorEspera($nombre, $desde, $hasta) {
        $st = $this->conexion->prepare(
            "SELECT COALESCE(SUM(rp.cantidad), 0)
               FROM reserva_pedido rp
               JOIN solicitud s ON s.id_solicitud = rp.id_solicitud AND s.estado = 'PENDIENTE'
               JOIN reserva r   ON r.id_solicitud = rp.id_solicitud
               JOIN reserva_espera e ON e.id_solicitud_generada = rp.id_solicitud
              WHERE rp.nombre_material = ?
                AND TIMESTAMP(r.fecha, r.hora_inicio) < ? AND TIMESTAMP(r.fecha, r.hora_fin) > ?");
        $st->execute([$nombre, $hasta, $desde]);
        return (int)$st->fetchColumn();
    }

    // Internos

    private function insertarSolicitud($ciCliente, $fecha, $ini, $fin, array $mapa) {
        $this->conexion->prepare("INSERT INTO solicitud (estado, fecha_solicitud, ci_cliente) VALUES ('PENDIENTE', NOW(), ?)")
                       ->execute([$ciCliente]);
        $id = (int)$this->conexion->lastInsertId();
        $this->conexion->prepare("INSERT INTO reserva (id_solicitud, fecha, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)")
                       ->execute([$id, $fecha, $ini, $fin]);
        $st = $this->conexion->prepare("INSERT INTO reserva_pedido (id_solicitud, nombre_material, cantidad) VALUES (?, ?, ?)");
        foreach ($mapa as $nombre => $cantidad) {
            $st->execute([$id, $nombre, $cantidad]);
        }
        return $id;
    }

    private function insertarEspera($ciCliente, $nombre, $cantidad, $fecha, $ini, $fin, DateTimeInterface $ahora, $origen = null) {
        $this->conexion->prepare(
            "INSERT INTO reserva_espera (ci_cliente, nombre_material, cantidad, fecha, hora_inicio, hora_fin, estado, fecha_alta, id_solicitud_origen)
             VALUES (?, ?, ?, ?, ?, ?, 'ESPERANDO', ?, ?)")
            ->execute([$ciCliente, $nombre, $cantidad, $fecha, $ini, $fin, $ahora->format('Y-m-d H:i:s'), $origen]);
        return (int)$this->conexion->lastInsertId();
    }

    // Falla, con el detalle por producto, si en la franja no alcanza alguna línea
    private function verificarStock(array $items, $desde, $hasta, $excluirId, DateTimeInterface $ahora, $mensaje) {
        $errores = [];
        $detalle = [];
        foreach ($items as $i) {
            $ev = $this->disp->evaluar($i['nombre'], $desde, $hasta, $excluirId, $ahora);
            if (!$ev['existe']) {
                $errores[] = "«{$i['nombre']}» no existe en el inventario.";
            } elseif ($ev['libres'] < $i['cantidad']) {
                $falta = $i['cantidad'] - $ev['libres'];
                $errores[] = sprintf('«%s»: se piden %d y hay %d libres en esa franja (%d en servicio, %d reservadas, %d prestadas).',
                    $i['nombre'], $i['cantidad'], $ev['libres'], $ev['capacidad'], $ev['reservadas'], $ev['prestadas']);
                $detalle[] = ['nombre' => $i['nombre'], 'solicitado' => $i['cantidad'], 'libres' => $ev['libres'], 'faltan' => $falta,
                              'capacidad' => $ev['capacidad'], 'reservadas' => $ev['reservadas'], 'prestadas' => $ev['prestadas'],
                              'proximos' => $ev['proximos'], 'completo_desde' => Disponibilidad::completoDesde($ev['proximos'], $falta)];
            }
        }
        if ($errores) {
            if ($detalle) {
                throw new ErrorDisponibilidad(ErrorDisponibilidad::SIN_STOCK, $mensaje, ['items' => $detalle], $errores);
            }
            throw new ErrorNegocio($mensaje, $errores);
        }
    }

    // Asigna las unidades a cada línea. Con series, una por cada unidad pedida; con lotes se reparte
    // entre las filas que tienen stock. Primero usa las que están DISPONIBLES ahora.
    private function asignarUnidades($idSolicitud, array $items, $desde, $hasta) {
        $ins = $this->conexion->prepare("INSERT INTO reserva_material (id_solicitud, id_material, cantidad) VALUES (?, ?, ?)");
        foreach ($items as $i) {
            $st = $this->conexion->prepare(
                "SELECT id_material, tipo_control, cantidad_total FROM material
                  WHERE nombre = ? AND estado NOT IN ('DADO_DE_BAJA', 'EN_MANTENIMIENTO')
                  ORDER BY (estado = 'DISPONIBLE') DESC, id_material");
            $st->execute([$i['nombre']]);
            $filas = $st->fetchAll(PDO::FETCH_ASSOC);

            // Lo que usan otras reservas aprobadas que se pisan (el máximo a la vez)
            $st = $this->conexion->prepare(
                "SELECT rm.id_material, rm.cantidad,
                        TIMESTAMP(r.fecha, r.hora_inicio) AS inicio, TIMESTAMP(r.fecha, r.hora_fin) AS fin
                   FROM reserva_material rm
                   JOIN material m  ON m.id_material = rm.id_material
                   JOIN solicitud s ON s.id_solicitud = rm.id_solicitud AND s.estado IN ('APROBADO', 'ACTIVO')
                   JOIN reserva r   ON r.id_solicitud = rm.id_solicitud
                  WHERE m.nombre = ? AND rm.id_solicitud <> ?
                    AND TIMESTAMP(r.fecha, r.hora_inicio) < ? AND TIMESTAMP(r.fecha, r.hora_fin) > ?");
            $st->execute([$i['nombre'], (int)$idSolicitud, $hasta, $desde]);
            $porFila = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $porFila[(int)$u['id_material']][] = ['inicio' => $u['inicio'], 'fin' => $u['fin'], 'cantidad' => (int)$u['cantidad']];
            }
            $usadas = [];
            foreach ($porFila as $idMat => $intervalos) {
                $usadas[$idMat] = Disponibilidad::pico($intervalos, $desde, $hasta);
            }

            $restante = (int)$i['cantidad'];
            foreach ($filas as $fila) {
                if ($restante <= 0) break;
                $total = $fila['tipo_control'] === 'SERIE' ? 1 : (int)$fila['cantidad_total'];
                $libre = $total - ($usadas[(int)$fila['id_material']] ?? 0);
                $toma  = min($libre, $restante);
                if ($toma > 0) {
                    $ins->execute([$idSolicitud, (int)$fila['id_material'], $toma]);
                    $restante -= $toma;
                }
            }
            if ($restante > 0) {
                throw new ErrorNegocio("No se pudieron asignar las unidades de «{$i['nombre']}»: revisá el inventario.");
            }
        }
    }

    // Bloquea las filas de cada tipo en orden alfabético (igual que en préstamos)
    // Material que la persona tiene y no devolvió (préstamo o reserva ACTIVA).
    // Mientras tanto no puede reservar. Devuelve ['id_solicitud', 'tipo'] o null.
    public function usoSinDevolver($ciCliente) {
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud,
                    CASE WHEN r.id_solicitud IS NOT NULL THEN 'RESERVA' ELSE 'PRESTAMO' END AS tipo
               FROM solicitud s
          LEFT JOIN reserva  r ON r.id_solicitud = s.id_solicitud
          LEFT JOIN prestamo p ON p.id_solicitud = s.id_solicitud
              WHERE s.ci_cliente = ? AND s.estado = 'ACTIVO'
                AND (r.id_solicitud IS NOT NULL OR p.id_solicitud IS NOT NULL)
              ORDER BY s.id_solicitud DESC
              LIMIT 1");
        $st->execute([$ciCliente]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function bloquearTipos(array $nombres) {
        $nombres = array_values(array_unique($nombres));
        sort($nombres, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($nombres as $n) {
            $st = $this->conexion->prepare("SELECT id_material FROM material WHERE nombre = ? FOR UPDATE");
            $st->execute([$n]);
            $st->fetchAll();
        }
    }

    private function itemsDe($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT nombre_material AS nombre, cantidad FROM reserva_pedido WHERE id_solicitud = ? ORDER BY nombre_material");
        $st->execute([(int)$idSolicitud]);
        return array_map(function ($f) { return ['nombre' => $f['nombre'], 'cantidad' => (int)$f['cantidad']]; },
                         $st->fetchAll(PDO::FETCH_ASSOC));
    }

    private function itemsDeMapa(array $mapa) {
        $items = [];
        foreach ($mapa as $nombre => $cantidad) $items[] = ['nombre' => $nombre, 'cantidad' => $cantidad];
        return $items;
    }

    private function bloquearReserva($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud, s.estado, s.ci_cliente, r.fecha, r.hora_inicio, r.hora_fin
               FROM solicitud s JOIN reserva r ON r.id_solicitud = s.id_solicitud
              WHERE s.id_solicitud = ? FOR UPDATE");
        $st->execute([(int)$idSolicitud]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            throw new ErrorNegocio("No existe la reserva #{$idSolicitud}.");
        }
        return $r;
    }

    // Devuelve nombre => cantidad, juntando repetidos y sacando los vacíos
    private function normalizarItems(array $items) {
        $mapa = [];
        foreach ($items as $clave => $i) {
            if (is_array($i)) {
                $nombre = trim((string)($i['nombre'] ?? ''));
                $cant   = $i['cantidad'] ?? 0;
            } else {                         // formato {nombre: cantidad}
                $nombre = trim((string)$clave);
                $cant   = $i;
            }
            if ($nombre === '') continue;
            if (!is_numeric($cant) || (int)$cant != $cant || (int)$cant < 0) {
                throw new ErrorNegocio("La cantidad de «{$nombre}» no es válida.");
            }
            $cant = (int)$cant;
            if ($cant === 0) continue;
            if ($cant > 1000) {
                throw new ErrorNegocio("La cantidad de «{$nombre}» es demasiado grande.");
            }
            $mapa[$nombre] = ($mapa[$nombre] ?? 0) + $cant;
        }
        if (!$mapa) {
            throw new ErrorNegocio('Elegí al menos un material y su cantidad.');
        }
        return $mapa;
    }

    // Valida la fecha y las horas; devuelve [fecha, inicio, fin]
    private function normalizarFranja($fecha, $horaInicio, $horaFin, $validarPasado = true) {
        $fecha = $this->fechaValida($fecha);
        $ini = $this->horaValida($horaInicio, 'de inicio');
        $fin = $this->horaValida($horaFin, 'de fin');
        if ($fin <= $ini) {
            throw new ErrorNegocio('La hora de fin tiene que ser posterior a la de inicio.');
        }
        if ($validarPasado) {
            $tz = new DateTimeZone('America/Montevideo');
            if (new DateTimeImmutable("$fecha $ini", $tz) < new DateTimeImmutable('now', $tz)) {
                throw new ErrorNegocio('La reserva no puede empezar en el pasado.');
            }
        }
        return [$fecha, $ini, $fin];
    }

    private function fechaValida($fecha) {
        $fecha = trim((string)$fecha);
        $d = DateTime::createFromFormat('!Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            throw new ErrorNegocio('La fecha no es válida. Usá el formato AAAA-MM-DD.');
        }
        return $fecha;
    }

    private function horaValida($hora, $cual) {
        $hora = trim((string)$hora);
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $hora)) {
            throw new ErrorNegocio("La hora {$cual} no es válida. Usá el formato HH:MM.");
        }
        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }

    // Usa READ COMMITTED para que, después de esperar un bloqueo, se vea lo que otro administrador acaba de confirmar
    private function enTransaccion(callable $fn) {
        $this->conexion->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $this->conexion->beginTransaction();
        try {
            $r = $fn();
            $this->conexion->commit();
            return $r === null ? true : $r;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) $this->conexion->rollBack();
            throw $e;
        }
    }

    // Clientes activos, para que el administrador elija a nombre de quién reserva
    public function obtenerListadoClientesAdmin() {
        $st = $this->conexion->query(
            "SELECT ci, CONCAT(nombre, ' ', apellido) AS nombre
               FROM usuario
              WHERE rol = 'CLIENTE' AND estado_cuenta = 'ACTIVA'
              ORDER BY nombre, apellido");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lo mismo que catalogo(), pero con la fecha en dd/mm/aaaa
    public function obtenerCatalogoDisponibilidad($fecha, $horaInicio, $horaFin) {
        if (strpos((string)$fecha, '/') !== false) {
            $partes = explode('/', $fecha);
            if (count($partes) === 3) {
                $fecha = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
            }
        }
        return $this->catalogo($fecha, $horaInicio, $horaFin);
    }
}
