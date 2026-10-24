<?php
/**
 * Modelo_Prestamo.php  (v7)
 *
 * Ciclo de un préstamo:
 *   1. solicitar  (cliente)  -> PENDIENTE. Pide por nombre: «2 Ceibalitas».
 *   2. aprobar    (admin)    -> APROBADO. Verifica que haya unidades libres.
 *      rechazar   (admin)    -> RECHAZADO, con motivo.
 *   3. entregar   (admin)    -> ACTIVO. El admin indica qué series entrega;
 *                               cada unidad debe estar DISPONIBLE y pasa a PRESTADO.
 *   4. devolver   (admin)    -> cada unidad vuelve DISPONIBLE o EN_MANTENIMIENTO.
 *                               Cuando vuelve todo, la solicitud pasa a FINALIZADO
 *                               y se archiva en historial_solicitud (RNF7).
 *   cancelar (cliente o admin) desde PENDIENTE o APROBADO -> CANCELADO.
 */
require_once __DIR__ . '/../../Config/Conexion.php';
require_once __DIR__ . '/../../Config/Estados.php';

class Modelo_Prestamo {
    /** @var PDO */
    private $conexion;

    public function __construct(?PDO $conexion = null) {
        $this->conexion = $conexion ?: Conexion::conectar();
    }

    /* ==============================================================
       CONSULTAS
       ============================================================== */

    /** Lista préstamos, opcionalmente filtrados por estado o por cliente. */
    public function listar($estado = null, $ciCliente = null) {
        $sql = "SELECT s.id_solicitud, s.estado, s.fecha_solicitud, s.fecha_validacion, s.motivo_rechazo,
                       s.ci_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente,
                       p.materia, p.horas_solicitadas, p.fecha_entrega, p.fecha_devolucion,
                       (SELECT GROUP_CONCAT(CONCAT(pp.cantidad, ' × ', pp.nombre_material) ORDER BY pp.nombre_material SEPARATOR ', ')
                          FROM prestamo_pedido pp WHERE pp.id_solicitud = s.id_solicitud) AS pedido
                  FROM solicitud s
                  JOIN prestamo p ON p.id_solicitud = s.id_solicitud
                  JOIN usuario  u ON u.ci = s.ci_cliente
                 WHERE 1 = 1";
        $param = [];
        if ($estado !== null && $estado !== '') {
            if (!in_array($estado, EstadoSolicitud::TODOS, true)) {
                throw new ErrorNegocio('Estado de solicitud no válido.');
            }
            $sql .= " AND s.estado = ?";
            $param[] = $estado;
        }
        if ($ciCliente !== null) {
            $sql .= " AND s.ci_cliente = ?";
            $param[] = $ciCliente;
        }
        $sql .= " ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC";
        $st = $this->conexion->prepare($sql);
        $st->execute($param);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Solicitud completa: cabecera, lo pedido (con disponibilidad actual) y lo entregado. */
    public function detalle($idSolicitud) {
        $cab = $this->listarUna($idSolicitud);

        $st = $this->conexion->prepare(
            "SELECT nombre_material, cantidad FROM prestamo_pedido WHERE id_solicitud = ? ORDER BY nombre_material");
        $st->execute([$idSolicitud]);
        $pedido = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $linea) {
            $linea['disponibilidad'] = $this->disponibilidad($linea['nombre_material'], $idSolicitud);
            $pedido[] = $linea;
        }

        $st = $this->conexion->prepare(
            "SELECT pm.id_material, m.id_serie, m.tipo_control, m.nombre, m.estado AS estado_actual,
                    pm.cantidad, pm.fecha_devolucion, pm.cantidad_devuelta, pm.estado_devolucion, pm.observacion
               FROM prestamo_material pm
               JOIN material m ON m.id_material = pm.id_material
              WHERE pm.id_solicitud = ?
              ORDER BY m.nombre, m.id_serie");
        $st->execute([$idSolicitud]);

        $cab['pedido']    = $pedido;
        $cab['entregado'] = $st->fetchAll(PDO::FETCH_ASSOC);
        return $cab;
    }

    /**
     * Disponibilidad de un tipo de material (por nombre):
     *   disponibles  = unidades en estado DISPONIBLE (o stock disponible si es CANTIDAD)
     *   comprometidas = pedidas en solicitudes APROBADAS que todavía no se entregaron
     *   libres       = disponibles - comprometidas
     */
    public function disponibilidad($nombre, $excluirSolicitud = 0) {
        $st = $this->conexion->prepare(
            "SELECT tipo_control,
                    SUM(estado <> 'DADO_DE_BAJA')                                         AS en_inventario,
                    SUM(CASE WHEN estado = 'DISPONIBLE' THEN cantidad_disponible ELSE 0 END) AS disponibles,
                    SUM(estado = 'PRESTADO')                                              AS prestadas,
                    SUM(estado = 'EN_MANTENIMIENTO')                                      AS en_mantenimiento,
                    SUM(CASE WHEN estado <> 'DADO_DE_BAJA' THEN cantidad_total ELSE 0 END) AS total
               FROM material
              WHERE nombre = ? AND estado <> 'DADO_DE_BAJA'
              GROUP BY tipo_control");
        $st->execute([$nombre]);
        $fila = $st->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            return ['existe' => false, 'tipo_control' => null, 'total' => 0, 'disponibles' => 0,
                    'prestadas' => 0, 'en_mantenimiento' => 0, 'comprometidas' => 0, 'libres' => 0];
        }

        $st = $this->conexion->prepare(
            "SELECT COALESCE(SUM(pp.cantidad), 0)
               FROM prestamo_pedido pp
               JOIN solicitud s ON s.id_solicitud = pp.id_solicitud
              WHERE s.estado = 'APROBADO' AND pp.nombre_material = ? AND s.id_solicitud <> ?");
        $st->execute([$nombre, (int)$excluirSolicitud]);
        $comprometidas = (int)$st->fetchColumn();

        $disponibles = (int)$fila['disponibles'];
        return [
            'existe'           => true,
            'tipo_control'     => $fila['tipo_control'],
            'total'            => (int)$fila['total'],
            'disponibles'      => $disponibles,
            'prestadas'        => (int)$fila['prestadas'],
            'en_mantenimiento' => (int)$fila['en_mantenimiento'],
            'comprometidas'    => $comprometidas,
            'libres'           => max(0, $disponibles - $comprometidas),
        ];
    }

    /** Para el formulario de entrega: unidades DISPONIBLES de cada línea del pedido. */
    public function unidadesParaEntregar($idSolicitud) {
        $det = $this->detalle($idSolicitud);
        foreach ($det['pedido'] as &$linea) {
            $st = $this->conexion->prepare(
                "SELECT id_material, id_serie, tipo_control, cantidad_disponible
                   FROM material
                  WHERE nombre = ? AND estado = 'DISPONIBLE'
                  ORDER BY id_serie");
            $st->execute([$linea['nombre_material']]);
            $linea['unidades'] = $st->fetchAll(PDO::FETCH_ASSOC);
        }
        return $det;
    }

    /** Traza de estados de un material (para la vista del administrador). */
    public function historialMaterial($idMaterial) {
        $st = $this->conexion->prepare(
            "SELECT h.fecha, h.evento, h.estado_anterior, h.estado_nuevo, h.id_solicitud, h.motivo,
                    CONCAT(u.nombre, ' ', u.apellido) AS responsable
               FROM historial_estado_material h
               LEFT JOIN usuario u ON u.ci = h.ci_responsable
              WHERE h.id_material = ?
              ORDER BY h.fecha DESC, h.id_cambio DESC");
        $st->execute([$idMaterial]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Últimos movimientos de estado de todos los materiales (historial general del administrador). */
    public function historialGeneral($limite = 300) {
        $limite = max(1, min(1000, (int)$limite));
        $st = $this->conexion->query(
            "SELECT h.fecha, h.evento, h.estado_anterior, h.estado_nuevo, h.id_solicitud, h.motivo,
                    m.nombre AS material, m.id_serie,
                    CONCAT(u.nombre, ' ', u.apellido) AS responsable
               FROM historial_estado_material h
               JOIN material m ON m.id_material = h.id_material
               LEFT JOIN usuario u ON u.ci = h.ci_responsable
              ORDER BY h.fecha DESC, h.id_cambio DESC
              LIMIT $limite");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Préstamo del cliente que le impide pedir otro: uno APROBADO (todavía sin entregar)
     * o ACTIVO (entregado y sin devolver). Devuelve ['id_solicitud', 'estado'] o null.
     */
    public function prestamoBloqueante($ciCliente) {
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud, s.estado
               FROM solicitud s
               JOIN prestamo p ON p.id_solicitud = s.id_solicitud
              WHERE s.ci_cliente = ? AND s.estado IN ('APROBADO', 'ACTIVO')
              ORDER BY s.id_solicitud DESC
              LIMIT 1");
        $st->execute([$ciCliente]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ==============================================================
       TRANSICIONES DE LA SOLICITUD
       ============================================================== */

    /**
     * El cliente pide materiales por nombre.
     * @param array $items [['nombre' => 'Ceibalita', 'cantidad' => 2], ...]
     */
    public function solicitar($ciCliente, $materia, $horas, array $items) {
        $bloqueante = $this->prestamoBloqueante($ciCliente);
        if ($bloqueante) {
            $situacion = $bloqueante['estado'] === EstadoSolicitud::ACTIVO
                ? 'entregado y todavía sin devolver'
                : 'aprobado y todavía sin entregar';
            throw new ErrorNegocio("Ya tenés el préstamo #{$bloqueante['id_solicitud']} {$situacion}. "
                                 . 'No podés solicitar otro hasta que se finalice o se cancele.');
        }
        $horas = (int)$horas;
        if ($horas <= 0) {
            throw new ErrorNegocio('Indicá cuántas horas necesitás el material.');
        }
        $pedido = $this->normalizarItems($items);

        $errores = [];
        foreach ($pedido as $nombre => $cantidad) {
            $d = $this->disponibilidad($nombre);
            if (!$d['existe']) {
                $errores[] = "«{$nombre}» no existe en el inventario.";
            } elseif ($cantidad > $d['total'] - $d['en_mantenimiento']) {
                $errores[] = "De «{$nombre}» hay " . ($d['total'] - $d['en_mantenimiento'])
                           . " en servicio y pediste {$cantidad}.";
            }
        }
        if ($errores) {
            throw new ErrorNegocio('No se pudo registrar la solicitud.', $errores);
        }

        $this->conexion->beginTransaction();
        try {
            $this->conexion->prepare(
                "INSERT INTO solicitud (estado, fecha_solicitud, ci_cliente) VALUES ('PENDIENTE', NOW(), ?)")
                ->execute([$ciCliente]);
            $id = (int)$this->conexion->lastInsertId();

            $this->conexion->prepare(
                "INSERT INTO prestamo (id_solicitud, materia, horas_solicitadas) VALUES (?, ?, ?)")
                ->execute([$id, trim((string)$materia) ?: null, $horas]);

            $st = $this->conexion->prepare(
                "INSERT INTO prestamo_pedido (id_solicitud, nombre_material, cantidad) VALUES (?, ?, ?)");
            foreach ($pedido as $nombre => $cantidad) {
                $st->execute([$id, $nombre, $cantidad]);
            }
            $this->conexion->commit();
            return $id;
        } catch (Exception $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    /** PENDIENTE -> APROBADO, solo si hay unidades libres para cada línea. */
    public function aprobar($idSolicitud, $ciAdmin) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin) {
            $sol = $this->bloquearSolicitud($idSolicitud);
            EstadoSolicitud::destino($sol['estado'], EstadoSolicitud::APROBAR);

            $errores = [];
            foreach ($this->pedido($idSolicitud) as $linea) {
                // Bloquea las unidades del tipo para que dos aprobaciones simultáneas no compitan
                $this->conexion->prepare("SELECT id_material FROM material WHERE nombre = ? FOR UPDATE")
                               ->execute([$linea['nombre_material']]);
                $d = $this->disponibilidad($linea['nombre_material'], $idSolicitud);
                if ($d['libres'] < $linea['cantidad']) {
                    $errores[] = sprintf('«%s»: se piden %d y hay %d libres (%d disponibles, %d comprometidas en otras aprobaciones, %d en mantenimiento, %d prestadas).',
                        $linea['nombre_material'], $linea['cantidad'], $d['libres'], $d['disponibles'],
                        $d['comprometidas'], $d['en_mantenimiento'], $d['prestadas']);
                }
            }
            if ($errores) {
                throw new ErrorNegocio('No hay unidades suficientes para aprobar.', $errores);
            }

            $this->conexion->prepare(
                "UPDATE solicitud SET estado = 'APROBADO', fecha_validacion = NOW(), ci_administrador = ? WHERE id_solicitud = ?")
                ->execute([$ciAdmin, $idSolicitud]);
        });
    }

    /** PENDIENTE -> RECHAZADO, con motivo obligatorio. */
    public function rechazar($idSolicitud, $ciAdmin, $motivo) {
        if (trim((string)$motivo) === '') {
            throw new ErrorNegocio('Indicá el motivo del rechazo.');
        }
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin, $motivo) {
            $sol = $this->bloquearSolicitud($idSolicitud);
            EstadoSolicitud::destino($sol['estado'], EstadoSolicitud::RECHAZAR);
            $this->conexion->prepare(
                "UPDATE solicitud SET estado = 'RECHAZADO', fecha_validacion = NOW(), motivo_rechazo = ?, ci_administrador = ?
                  WHERE id_solicitud = ?")
                ->execute([trim($motivo), $ciAdmin, $idSolicitud]);
        });
    }

    /** PENDIENTE | APROBADO -> CANCELADO. El cliente solo cancela las suyas. */
    public function cancelar($idSolicitud, $ciUsuario, $esAdmin) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciUsuario, $esAdmin) {
            $sol = $this->bloquearSolicitud($idSolicitud);
            if (!$esAdmin && $sol['ci_cliente'] !== $ciUsuario) {
                throw new ErrorNegocio('Solo podés cancelar tus propias solicitudes.');
            }
            EstadoSolicitud::destino($sol['estado'], EstadoSolicitud::CANCELAR);
            $this->conexion->prepare("UPDATE solicitud SET estado = 'CANCELADO' WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);
        });
    }

    /**
     * APROBADO -> ACTIVO. El administrador indica qué series entrega.
     * Cada serie se valida contra su estado individual:
     *   DISPONIBLE -> se presta (pasa a PRESTADO)
     *   PRESTADO / EN_MANTENIMIENTO / DADO_DE_BAJA -> error con el motivo
     * Los consumibles (CANTIDAD) se descuentan del stock.
     *
     * @param string[] $series números de serie de los equipos entregados
     */
    public function entregar($idSolicitud, $ciAdmin, array $series) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin, $series) {
            $sol = $this->bloquearSolicitud($idSolicitud);
            EstadoSolicitud::destino($sol['estado'], EstadoSolicitud::ENTREGAR);

            $pedido = [];
            foreach ($this->pedido($idSolicitud) as $l) {
                $pedido[mb_strtolower($l['nombre_material'])] = $l;
            }

            $errores   = [];
            $unidades  = [];          // equipos validados, agrupados por nombre
            $vistas    = [];

            // 1. Validar cada serie contra su estado individual
            foreach ($series as $serie) {
                $serie = strtoupper(trim((string)$serie));
                if ($serie === '') continue;
                if (isset($vistas[$serie])) {
                    $errores[] = "La serie {$serie} está repetida.";
                    continue;
                }
                $vistas[$serie] = true;

                $st = $this->conexion->prepare(
                    "SELECT id_material, id_serie, nombre, tipo_control, estado FROM material WHERE id_serie = ? FOR UPDATE");
                $st->execute([$serie]);
                $u = $st->fetch(PDO::FETCH_ASSOC);

                if (!$u) {
                    $errores[] = "La serie {$serie} no existe.";
                    continue;
                }
                $clave = mb_strtolower($u['nombre']);
                if (!isset($pedido[$clave])) {
                    $errores[] = "La serie {$serie} corresponde a «{$u['nombre']}», que no está en el pedido.";
                    continue;
                }
                $motivo = $this->motivoNoPrestable($u);
                if ($motivo !== null) {
                    $errores[] = $motivo;
                    continue;
                }
                $unidades[$clave][] = $u;
            }

            // 2. Cada línea del pedido debe quedar cubierta exactamente
            $consumibles = [];
            foreach ($pedido as $clave => $linea) {
                $d = $this->disponibilidad($linea['nombre_material'], $idSolicitud);
                if ($d['tipo_control'] === 'SERIE') {
                    $dadas = count($unidades[$clave] ?? []);
                    if ($dadas !== (int)$linea['cantidad']) {
                        $errores[] = sprintf('«%s»: se pidieron %d y se indicaron %d series válidas.',
                                             $linea['nombre_material'], $linea['cantidad'], $dadas);
                    }
                } elseif ($d['tipo_control'] === 'CANTIDAD') {
                    $st = $this->conexion->prepare(
                        "SELECT id_material, nombre, estado, cantidad_disponible
                           FROM material
                          WHERE nombre = ? AND tipo_control = 'CANTIDAD' AND estado <> 'DADO_DE_BAJA'
                          FOR UPDATE");
                    $st->execute([$linea['nombre_material']]);
                    $c = $st->fetch(PDO::FETCH_ASSOC);
                    if ($c['estado'] !== EstadoMaterial::DISPONIBLE) {
                        $errores[] = "«{$c['nombre']}» está en mantenimiento.";
                    } elseif ((int)$c['cantidad_disponible'] < (int)$linea['cantidad']) {
                        $errores[] = sprintf('«%s»: se piden %d y quedan %d.', $c['nombre'], $linea['cantidad'], $c['cantidad_disponible']);
                    } else {
                        $consumibles[] = [$c, (int)$linea['cantidad']];
                    }
                } else {
                    $errores[] = "«{$linea['nombre_material']}» ya no está en el inventario.";
                }
            }
            if ($errores) {
                throw new ErrorNegocio('No se pudo entregar el préstamo.', $errores);
            }

            // 3. Aplicar: equipos a PRESTADO, consumibles descontados
            $insLinea = $this->conexion->prepare(
                "INSERT INTO prestamo_material (id_solicitud, id_material, cantidad) VALUES (?, ?, ?)");
            foreach ($unidades as $grupo) {
                foreach ($grupo as $u) {
                    $nuevo = EstadoMaterial::destino($u['estado'], EstadoMaterial::ENTREGAR, "La serie {$u['id_serie']}");
                    $this->conexion->prepare(
                        "UPDATE material SET estado = ?, cantidad_disponible = 0 WHERE id_material = ?")
                        ->execute([$nuevo, $u['id_material']]);
                    $insLinea->execute([$idSolicitud, $u['id_material'], 1]);
                    HistorialEstado::registrarHistorialEstado($this->conexion, $u['id_material'], $u['estado'], $nuevo,
                                               EstadoMaterial::ENTREGAR, $ciAdmin, $idSolicitud);
                }
            }
            foreach ($consumibles as [$c, $cantidad]) {
                $this->conexion->prepare(
                    "UPDATE material SET cantidad_disponible = cantidad_disponible - ? WHERE id_material = ?")
                    ->execute([$cantidad, $c['id_material']]);
                $insLinea->execute([$idSolicitud, $c['id_material'], $cantidad]);
            }

            $this->conexion->prepare("UPDATE solicitud SET estado = 'ACTIVO' WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);
            $this->conexion->prepare("UPDATE prestamo SET fecha_entrega = NOW() WHERE id_solicitud = ?")
                           ->execute([$idSolicitud]);
        });
    }

    /**
     * Devolución (total o parcial) de un préstamo ACTIVO.
     * @param array $items [['id_material' => 3, 'estado' => 'DISPONIBLE'|'EN_MANTENIMIENTO',
     *                       'cantidad_devuelta' => n (solo consumibles), 'observacion' => '...'], ...]
     * @return array ['finalizado' => bool, 'pendientes' => int]
     */
    public function devolver($idSolicitud, $ciAdmin, array $items) {
        if (!$items) {
            throw new ErrorNegocio('Indicá qué materiales se devuelven.');
        }
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin, $items) {
            $sol = $this->bloquearSolicitud($idSolicitud);
            if ($sol['estado'] !== EstadoSolicitud::ACTIVO) {
                throw new ErrorNegocio('Solo se registran devoluciones de préstamos activos.');
            }

            $errores = [];
            foreach ($items as $it) {
                $idMat = (int)($it['id_material'] ?? 0);
                $obs   = trim((string)($it['observacion'] ?? ''));
                $st = $this->conexion->prepare(
                    "SELECT pm.cantidad, pm.fecha_devolucion, m.id_material, m.id_serie, m.nombre, m.tipo_control, m.estado
                       FROM prestamo_material pm
                       JOIN material m ON m.id_material = pm.id_material
                      WHERE pm.id_solicitud = ? AND pm.id_material = ?
                      FOR UPDATE");
                $st->execute([$idSolicitud, $idMat]);
                $l = $st->fetch(PDO::FETCH_ASSOC);
                if (!$l) {
                    $errores[] = "El material #{$idMat} no forma parte de este préstamo.";
                    continue;
                }
                $desc = $l['id_serie'] ? "La serie {$l['id_serie']}" : "«{$l['nombre']}»";
                if ($l['fecha_devolucion'] !== null) {
                    $errores[] = "{$desc} ya fue devuelta.";
                    continue;
                }

                if ($l['tipo_control'] === 'SERIE') {
                    $estadoDev = $it['estado'] ?? EstadoMaterial::DISPONIBLE;
                    if ($estadoDev === EstadoMaterial::DISPONIBLE) {
                        $evento = EstadoMaterial::DEVOLVER;
                    } elseif ($estadoDev === EstadoMaterial::EN_MANTENIMIENTO) {
                        $evento = EstadoMaterial::DEVOLVER_CON_DANO;
                        if ($obs === '') {
                            $errores[] = "{$desc}: describí el daño en la observación.";
                            continue;
                        }
                    } else {
                        $errores[] = "{$desc}: al devolver solo puede quedar disponible o en mantenimiento.";
                        continue;
                    }
                    try {
                        $nuevo = EstadoMaterial::destino($l['estado'], $evento, $desc);
                    } catch (ErrorNegocio $e) {
                        $errores[] = $e->getMessage();
                        continue;
                    }
                    $this->conexion->prepare(
                        "UPDATE material SET estado = ?, cantidad_disponible = ? WHERE id_material = ?")
                        ->execute([$nuevo, $nuevo === EstadoMaterial::DISPONIBLE ? 1 : 0, $idMat]);
                    $this->conexion->prepare(
                        "UPDATE prestamo_material
                            SET fecha_devolucion = NOW(), cantidad_devuelta = 1, estado_devolucion = ?, observacion = ?
                          WHERE id_solicitud = ? AND id_material = ?")
                        ->execute([$nuevo, $obs ?: null, $idSolicitud, $idMat]);
                    HistorialEstado::registrarHistorialEstado($this->conexion, $idMat, $l['estado'], $nuevo, $evento,
                                               $ciAdmin, $idSolicitud, $obs);
                } else {
                    // Consumibles: vuelve lo que vuelve; lo que falta se descuenta del total
                    $dev = isset($it['cantidad_devuelta']) ? (int)$it['cantidad_devuelta'] : (int)$l['cantidad'];
                    if ($dev < 0 || $dev > (int)$l['cantidad']) {
                        $errores[] = "{$desc}: la cantidad devuelta debe estar entre 0 y {$l['cantidad']}.";
                        continue;
                    }
                    $faltan = (int)$l['cantidad'] - $dev;
                    if ($faltan > 0 && $obs === '') {
                        $errores[] = "{$desc}: faltan {$faltan}; explicá el motivo en la observación.";
                        continue;
                    }
                    $this->conexion->prepare(
                        "UPDATE material
                            SET cantidad_disponible = cantidad_disponible + ?, cantidad_total = cantidad_total - ?
                          WHERE id_material = ?")
                        ->execute([$dev, $faltan, $idMat]);
                    $this->conexion->prepare(
                        "UPDATE prestamo_material
                            SET fecha_devolucion = NOW(), cantidad_devuelta = ?, estado_devolucion = 'DISPONIBLE', observacion = ?
                          WHERE id_solicitud = ? AND id_material = ?")
                        ->execute([$dev, $obs ?: null, $idSolicitud, $idMat]);
                }
            }
            if ($errores) {
                throw new ErrorNegocio('No se pudo registrar la devolución.', $errores);
            }

            // ¿Queda algo sin devolver?
            $st = $this->conexion->prepare(
                "SELECT COUNT(*) FROM prestamo_material WHERE id_solicitud = ? AND fecha_devolucion IS NULL");
            $st->execute([$idSolicitud]);
            $pendientes = (int)$st->fetchColumn();

            if ($pendientes === 0) {
                EstadoSolicitud::destino($sol['estado'], EstadoSolicitud::FINALIZAR);
                $this->conexion->prepare("UPDATE solicitud SET estado = 'FINALIZADO' WHERE id_solicitud = ?")
                               ->execute([$idSolicitud]);
                $this->conexion->prepare("UPDATE prestamo SET fecha_devolucion = NOW() WHERE id_solicitud = ?")
                               ->execute([$idSolicitud]);
                // Copia en el historial (RF30, RNF7): la solicitud no se borra
                $this->conexion->prepare(
                    "INSERT INTO historial_solicitud
                         (id_solicitud_origen, tipo, fecha_archivo, estado, fecha_solicitud, fecha_validacion,
                          motivo_rechazo, ci_cliente, ci_administrador)
                     SELECT id_solicitud, 'Prestamo', NOW(), 'FINALIZADO', fecha_solicitud, fecha_validacion,
                            motivo_rechazo, ci_cliente, ci_administrador
                       FROM solicitud WHERE id_solicitud = ?")
                    ->execute([$idSolicitud]);
            }
            return ['finalizado' => $pendientes === 0, 'pendientes' => $pendientes];
        });
    }

    /**
     * Edición de un préstamo APROBADO o ACTIVO por el administrador (sube o baja cantidades).
     *
     * APROBADO (todavía no se entregó): se cambia la cantidad pedida de cada línea.
     *   $cambios['pedido'] = [['nombre' => 'Ceibalita', 'cantidad' => 3], ...]
     *   Cada cantidad se valida contra las unidades libres (sin contar lo comprometido en otros préstamos).
     *
     * ACTIVO (ya entregado):
     *   $cambios['cantidades']     = [['nombre' => 'Buzzer', 'cantidad' => 4], ...]  consumibles: cantidad nueva
     *   $cambios['agregar_series'] = ['CEIB-0007', ...]   equipos que se suman (deben estar DISPONIBLES)
     *   $cambios['quitar']         = [id_material, ...]   equipos que vuelven al CeiLab (quedan DISPONIBLES)
     *
     * Cada producto debe quedar con al menos 1 unidad: para devolver un producto entero
     * se usa «Registrar devolución».
     */
    public function editar($idSolicitud, $ciAdmin, array $cambios) {
        return $this->enTransaccion(function () use ($idSolicitud, $ciAdmin, $cambios) {
            $sol = $this->bloquearSolicitud($idSolicitud);
            if ($sol['estado'] === EstadoSolicitud::APROBADO) {
                $this->editarAprobado($idSolicitud, $cambios);
            } elseif ($sol['estado'] === EstadoSolicitud::ACTIVO) {
                $this->editarActivo($idSolicitud, $ciAdmin, $cambios);
            } else {
                throw new ErrorNegocio('Solo se pueden editar préstamos aprobados o activos.');
            }
        });
    }

    /** APROBADO: cambia las cantidades del pedido, validando que haya unidades libres. */
    private function editarAprobado($idSolicitud, array $cambios) {
        $actual = [];
        foreach ($this->pedido($idSolicitud) as $l) {
            $actual[mb_strtolower($l['nombre_material'])] = $l;
        }

        $errores = [];
        $aplicar = [];
        foreach ((array)($cambios['pedido'] ?? []) as $it) {
            $nombre   = trim((string)($it['nombre'] ?? ''));
            $cantidad = (int)($it['cantidad'] ?? 0);
            $clave    = mb_strtolower($nombre);
            if (!isset($actual[$clave])) {
                $errores[] = "«{$nombre}» no está en este préstamo.";
                continue;
            }
            $linea = $actual[$clave];
            if ($cantidad < 1) {
                $errores[] = "«{$linea['nombre_material']}»: la cantidad mínima es 1. Para sacar el producto cancelá el préstamo.";
                continue;
            }
            if ($cantidad === (int)$linea['cantidad']) {
                continue;   // sin cambios en esta línea
            }
            // Bloquea las unidades del tipo para que dos ediciones/aprobaciones simultáneas no compitan
            $this->conexion->prepare("SELECT id_material FROM material WHERE nombre = ? FOR UPDATE")
                           ->execute([$linea['nombre_material']]);
            $d = $this->disponibilidad($linea['nombre_material'], $idSolicitud);
            if ($cantidad > $d['libres']) {
                $errores[] = sprintf('«%s»: pediste %d y solo hay %d libres para este préstamo (%d disponibles, %d comprometidas en otros préstamos aprobados).',
                    $linea['nombre_material'], $cantidad, $d['libres'], $d['disponibles'], $d['comprometidas']);
                continue;
            }
            $aplicar[] = [$linea['nombre_material'], $cantidad];
        }
        if ($errores) {
            throw new ErrorNegocio('No se pudo editar el préstamo.', $errores);
        }
        if (!$aplicar) {
            throw new ErrorNegocio('No hay cambios para guardar.');
        }
        $up = $this->conexion->prepare(
            "UPDATE prestamo_pedido SET cantidad = ? WHERE id_solicitud = ? AND nombre_material = ?");
        foreach ($aplicar as [$nombre, $cantidad]) {
            $up->execute([$cantidad, $idSolicitud, $nombre]);
        }
    }

    /** ACTIVO: suma o quita equipos y ajusta consumibles de un préstamo ya entregado. */
    private function editarActivo($idSolicitud, $ciAdmin, array $cambios) {
        // Lo entregado y todavía sin devolver
        $st = $this->conexion->prepare(
            "SELECT pm.id_material, pm.cantidad, m.id_serie, m.nombre, m.tipo_control, m.estado
               FROM prestamo_material pm
               JOIN material m ON m.id_material = pm.id_material
              WHERE pm.id_solicitud = ? AND pm.fecha_devolucion IS NULL
              FOR UPDATE");
        $st->execute([$idSolicitud]);
        $abiertas = [];     // id_material => fila
        $enMano   = [];     // nombre (minúsculas) => unidades que hoy tiene el cliente
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $abiertas[(int)$f['id_material']] = $f;
            $k = mb_strtolower($f['nombre']);
            $enMano[$k] = ($enMano[$k] ?? 0) + (int)$f['cantidad'];
        }
        $pedido = [];
        foreach ($this->pedido($idSolicitud) as $l) {
            $pedido[mb_strtolower($l['nombre_material'])] = $l;
        }

        $errores  = [];
        $delta    = [];     // nombre (minúsculas) => cambio neto de unidades
        $quitar   = [];     // equipos que vuelven
        $agregar  = [];     // equipos que se suman
        $ajustes  = [];     // consumibles: [fila, cantidad nueva]

        // 1. Equipos que se quitan
        foreach (array_unique(array_map('intval', (array)($cambios['quitar'] ?? []))) as $idMat) {
            if (!isset($abiertas[$idMat]) || $abiertas[$idMat]['tipo_control'] !== 'SERIE') {
                $errores[] = "El material #{$idMat} no es un equipo pendiente de devolución de este préstamo.";
                continue;
            }
            $quitar[] = $abiertas[$idMat];
            $k = mb_strtolower($abiertas[$idMat]['nombre']);
            $delta[$k] = ($delta[$k] ?? 0) - 1;
        }

        // 2. Equipos que se suman (por número de serie)
        $vistas = [];
        foreach ((array)($cambios['agregar_series'] ?? []) as $serie) {
            $serie = strtoupper(trim((string)$serie));
            if ($serie === '') continue;
            if (isset($vistas[$serie])) {
                $errores[] = "La serie {$serie} está repetida.";
                continue;
            }
            $vistas[$serie] = true;
            $st = $this->conexion->prepare(
                "SELECT id_material, id_serie, nombre, tipo_control, estado FROM material WHERE id_serie = ? FOR UPDATE");
            $st->execute([$serie]);
            $u = $st->fetch(PDO::FETCH_ASSOC);
            if (!$u) {
                $errores[] = "La serie {$serie} no existe.";
                continue;
            }
            $k = mb_strtolower($u['nombre']);
            if (!isset($pedido[$k])) {
                $errores[] = "La serie {$serie} corresponde a «{$u['nombre']}», que no está en este préstamo.";
                continue;
            }
            $motivo = $this->motivoNoPrestable($u);
            if ($motivo !== null) {
                $errores[] = $motivo;
                continue;
            }
            $agregar[] = $u;
            $delta[$k] = ($delta[$k] ?? 0) + 1;
        }

        // 3. Consumibles: cantidad nueva de cada línea
        foreach ((array)($cambios['cantidades'] ?? []) as $it) {
            $nombre = trim((string)($it['nombre'] ?? ''));
            $nueva  = (int)($it['cantidad'] ?? 0);
            $k      = mb_strtolower($nombre);
            $fila   = null;
            foreach ($abiertas as $f) {
                if (mb_strtolower($f['nombre']) === $k && $f['tipo_control'] === 'CANTIDAD') { $fila = $f; break; }
            }
            if (!$fila) {
                $errores[] = "«{$nombre}» no es un consumible pendiente de devolución de este préstamo.";
                continue;
            }
            if ($nueva < 1) {
                $errores[] = "«{$fila['nombre']}»: la cantidad mínima es 1. Para devolverlo entero usá «Registrar devolución».";
                continue;
            }
            if ($nueva === (int)$fila['cantidad']) {
                continue;
            }
            $ajustes[] = [$fila, $nueva];
            $delta[$k] = ($delta[$k] ?? 0) + ($nueva - (int)$fila['cantidad']);
        }

        // 4. Disponibilidad de lo que se suma (sin tocar lo comprometido en otros préstamos aprobados)
        foreach ($ajustes as [$fila, $nueva]) {
            $extra = $nueva - (int)$fila['cantidad'];
            if ($extra <= 0) continue;
            $st = $this->conexion->prepare(
                "SELECT estado, cantidad_disponible FROM material WHERE id_material = ? FOR UPDATE");
            $st->execute([$fila['id_material']]);
            $c = $st->fetch(PDO::FETCH_ASSOC);
            $d = $this->disponibilidad($fila['nombre'], $idSolicitud);
            if ($c['estado'] !== EstadoMaterial::DISPONIBLE) {
                $errores[] = "«{$fila['nombre']}» está en mantenimiento: no se puede sumar más.";
            } elseif ($extra > (int)$c['cantidad_disponible']) {
                $errores[] = sprintf('«%s»: querés sumar %d y quedan %d en el stock.', $fila['nombre'], $extra, $c['cantidad_disponible']);
            } elseif ($extra > $d['libres']) {
                $errores[] = sprintf('«%s»: querés sumar %d pero solo hay %d libres (el resto está comprometido en otros préstamos aprobados).',
                                     $fila['nombre'], $extra, $d['libres']);
            }
        }
        $sumaEquipos = [];
        foreach ($agregar as $u) {
            $k = mb_strtolower($u['nombre']);
            $sumaEquipos[$k] = ($sumaEquipos[$k] ?? 0) + 1;
        }
        foreach ($sumaEquipos as $k => $n) {
            $d = $this->disponibilidad($pedido[$k]['nombre_material'], $idSolicitud);
            if ($n > $d['libres']) {
                $errores[] = sprintf('«%s»: querés sumar %d equipos pero solo hay %d libres (el resto está comprometido en otros préstamos aprobados).',
                                     $pedido[$k]['nombre_material'], $n, $d['libres']);
            }
        }

        // 5. Cada producto debe seguir con al menos 1 unidad
        foreach ($delta as $k => $d) {
            if (($enMano[$k] ?? 0) + $d < 1) {
                $nombre = $pedido[$k]['nombre_material'] ?? $k;
                $errores[] = "«{$nombre}»: tiene que quedar al menos 1 unidad. Para devolverlo entero usá «Registrar devolución».";
            }
        }

        if ($errores) {
            throw new ErrorNegocio('No se pudo editar el préstamo.', $errores);
        }
        if (!$quitar && !$agregar && !$ajustes) {
            throw new ErrorNegocio('No hay cambios para guardar.');
        }

        // 6. Aplicar
        $insLinea = $this->conexion->prepare(
            "INSERT INTO prestamo_material (id_solicitud, id_material, cantidad) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE cantidad = 1, fecha_devolucion = NULL, cantidad_devuelta = NULL,
                                     estado_devolucion = NULL, observacion = NULL");
        foreach ($agregar as $u) {
            $nuevo = EstadoMaterial::destino($u['estado'], EstadoMaterial::ENTREGAR, "La serie {$u['id_serie']}");
            $this->conexion->prepare("UPDATE material SET estado = ?, cantidad_disponible = 0 WHERE id_material = ?")
                           ->execute([$nuevo, $u['id_material']]);
            $insLinea->execute([$idSolicitud, $u['id_material']]);
            HistorialEstado::registrarHistorialEstado($this->conexion, $u['id_material'], $u['estado'], $nuevo,
                EstadoMaterial::ENTREGAR, $ciAdmin, $idSolicitud, 'Agregado al editar el préstamo');
        }
        foreach ($quitar as $u) {
            $nuevo = EstadoMaterial::destino($u['estado'], EstadoMaterial::DEVOLVER, "La serie {$u['id_serie']}");
            $this->conexion->prepare("UPDATE material SET estado = ?, cantidad_disponible = 1 WHERE id_material = ?")
                           ->execute([$nuevo, $u['id_material']]);
            $this->conexion->prepare(
                "UPDATE prestamo_material
                    SET fecha_devolucion = NOW(), cantidad_devuelta = 1, estado_devolucion = ?, observacion = ?
                  WHERE id_solicitud = ? AND id_material = ?")
                ->execute([$nuevo, 'Retirado al editar el préstamo', $idSolicitud, $u['id_material']]);
            HistorialEstado::registrarHistorialEstado($this->conexion, $u['id_material'], $u['estado'], $nuevo,
                EstadoMaterial::DEVOLVER, $ciAdmin, $idSolicitud, 'Retirado al editar el préstamo');
        }
        foreach ($ajustes as [$fila, $nueva]) {
            $extra = $nueva - (int)$fila['cantidad'];     // positivo: sale más stock; negativo: vuelve stock
            $this->conexion->prepare(
                "UPDATE material SET cantidad_disponible = cantidad_disponible - ? WHERE id_material = ?")
                ->execute([$extra, $fila['id_material']]);
            $this->conexion->prepare(
                "UPDATE prestamo_material
                    SET cantidad = ?, observacion = CONCAT_WS(' · ', observacion, ?)
                  WHERE id_solicitud = ? AND id_material = ?")
                ->execute([$nueva, sprintf('Cantidad editada de %d a %d', $fila['cantidad'], $nueva),
                           $idSolicitud, $fila['id_material']]);
        }

        // 7. El pedido acompaña a lo entregado
        $up = $this->conexion->prepare(
            "UPDATE prestamo_pedido SET cantidad = ? WHERE id_solicitud = ? AND nombre_material = ?");
        foreach ($delta as $k => $d) {
            if ($d === 0 || !isset($pedido[$k])) continue;
            $up->execute([max(1, (int)$pedido[$k]['cantidad'] + $d), $idSolicitud, $pedido[$k]['nombre_material']]);
        }
    }

    /* ==============================================================
       AUXILIARES
       ============================================================== */

    /** null si la unidad se puede prestar; si no, el motivo para mostrar. */
    private function motivoNoPrestable(array $u) {
        $serie = $u['id_serie'];
        switch ($u['estado']) {
            case EstadoMaterial::DISPONIBLE:
                return null;
            case EstadoMaterial::PRESTADO:
                $st = $this->conexion->prepare(
                    "SELECT pm.id_solicitud FROM prestamo_material pm
                       JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                      WHERE pm.id_material = ? AND pm.fecha_devolucion IS NULL AND s.estado = 'ACTIVO'
                      LIMIT 1");
                $st->execute([$u['id_material']]);
                $otra = $st->fetchColumn();
                return "La serie {$serie} está prestada" . ($otra ? " (solicitud #{$otra})" : '') . '.';
            case EstadoMaterial::EN_MANTENIMIENTO:
                return "La serie {$serie} está en mantenimiento.";
            case EstadoMaterial::DADO_DE_BAJA:
                return "La serie {$serie} está dada de baja.";
            default:
                return "La serie {$serie} tiene un estado desconocido ({$u['estado']}).";
        }
    }

    private function normalizarItems(array $items) {
        $pedido = [];
        foreach ($items as $it) {
            $nombre   = trim((string)($it['nombre'] ?? ''));
            $cantidad = (int)($it['cantidad'] ?? 0);
            if ($nombre === '' || $cantidad <= 0) {
                throw new ErrorNegocio('Cada material del pedido necesita nombre y una cantidad mayor que cero.');
            }
            $clave = mb_strtolower($nombre);
            if (isset($pedido[$clave])) {
                $pedido[$clave]['cantidad'] += $cantidad;
            } else {
                $pedido[$clave] = ['nombre' => $nombre, 'cantidad' => $cantidad];
            }
        }
        if (!$pedido) {
            throw new ErrorNegocio('El pedido está vacío.');
        }
        $salida = [];
        foreach ($pedido as $p) {
            $salida[$this->nombreCanonico($p['nombre'])] = $p['cantidad'];
        }
        return $salida;
    }

    /** Usa el nombre tal como está escrito en el inventario. */
    private function nombreCanonico($nombre) {
        $st = $this->conexion->prepare(
            "SELECT nombre FROM material WHERE nombre = ? AND estado <> 'DADO_DE_BAJA' ORDER BY id_material LIMIT 1");
        $st->execute([$nombre]);
        return $st->fetchColumn() ?: $nombre;
    }

    private function pedido($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT nombre_material, cantidad FROM prestamo_pedido WHERE id_solicitud = ? ORDER BY nombre_material");
        $st->execute([$idSolicitud]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function bloquearSolicitud($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud, s.estado, s.ci_cliente
               FROM solicitud s JOIN prestamo p ON p.id_solicitud = s.id_solicitud
              WHERE s.id_solicitud = ? FOR UPDATE");
        $st->execute([$idSolicitud]);
        $sol = $st->fetch(PDO::FETCH_ASSOC);
        if (!$sol) {
            throw new ErrorNegocio("No existe el préstamo #{$idSolicitud}.");
        }
        return $sol;
    }

    private function listarUna($idSolicitud) {
        $st = $this->conexion->prepare(
            "SELECT s.id_solicitud, s.estado, s.fecha_solicitud, s.fecha_validacion, s.motivo_rechazo,
                    s.ci_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente, s.ci_administrador,
                    p.materia, p.horas_solicitadas, p.fecha_entrega, p.fecha_devolucion
               FROM solicitud s
               JOIN prestamo p ON p.id_solicitud = s.id_solicitud
               JOIN usuario  u ON u.ci = s.ci_cliente
              WHERE s.id_solicitud = ?");
        $st->execute([$idSolicitud]);
        $fila = $st->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new ErrorNegocio("No existe el préstamo #{$idSolicitud}.");
        }
        return $fila;
    }

    private function enTransaccion(callable $fn) {
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