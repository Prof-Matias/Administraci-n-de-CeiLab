-- =====================================================================
-- schema_reservas.sql  (se importa DESPUÉS de schema.sql; se puede repetir)
--
-- Tablas nuevas del módulo de Reservas. Las reservas se piden por TIPO de
-- material y cantidad (igual que los préstamos con prestamo_pedido), y las
-- unidades concretas se asignan al aprobar (reserva_material, que ya existía).
--
--   reserva_pedido    lo que pidió el cliente: tipo (nombre) y cantidad
--   reserva_espera    lista de espera: pedidos que no se pudieron cubrir
--   historial_reserva copia de cada reserva finalizada (RF45–RF47)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `reserva_pedido` (
  `id_solicitud`    int(11)      NOT NULL,
  `nombre_material` varchar(100) NOT NULL,
  `cantidad`        int(11)      NOT NULL CHECK (`cantidad` > 0),
  PRIMARY KEY (`id_solicitud`, `nombre_material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `reserva_espera` (
  `id_espera`            int(11)      NOT NULL AUTO_INCREMENT,
  `ci_cliente`           varchar(8)   NOT NULL,
  `nombre_material`      varchar(100) NOT NULL,
  `cantidad`             int(11)      NOT NULL CHECK (`cantidad` > 0),
  `fecha`                date         NOT NULL,
  `hora_inicio`          time         NOT NULL,
  `hora_fin`             time         NOT NULL,
  `estado`               varchar(20)  NOT NULL DEFAULT 'ESPERANDO'
                         CHECK (`estado` IN ('ESPERANDO', 'ATENDIDA', 'CANCELADA', 'VENCIDA')),
  `fecha_alta`           datetime     NOT NULL,
  `fecha_atencion`       datetime     DEFAULT NULL,
  `id_solicitud_generada` int(11)     DEFAULT NULL,
  `id_solicitud_origen`  int(11)      DEFAULT NULL,   -- reserva parcial desde la que se anotó en espera (si hubo)
  PRIMARY KEY (`id_espera`),
  KEY `idx_espera_estado` (`estado`, `nombre_material`),
  KEY `idx_espera_cliente` (`ci_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `historial_reserva` (
  `id_historial`        int(11)     NOT NULL AUTO_INCREMENT,
  `id_solicitud_origen` int(11)     NOT NULL,
  `fecha_archivo`       datetime    NOT NULL,
  `estado`              varchar(30) NOT NULL,
  `fecha`               date        NOT NULL,
  `hora_inicio`         time        NOT NULL,
  `hora_fin`            time        NOT NULL,
  `detalle`             text        NOT NULL,
  `fecha_solicitud`     datetime    DEFAULT NULL,
  `fecha_validacion`    datetime    DEFAULT NULL,
  `ci_cliente`          varchar(8)  NOT NULL,
  `ci_administrador`    varchar(8)  DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  UNIQUE KEY `uq_historial_reserva_origen` (`id_solicitud_origen`),
  KEY `idx_hist_reserva_cliente` (`ci_cliente`),
  KEY `idx_hist_reserva_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Relleno: reservas que ya existían (hechas por unidad, antes de este módulo) quedan también
-- como pedido por tipo y cantidad. Se puede repetir sin duplicar. Si importás schema_reservas.sql
-- ANTES de seed.sql, repetí este bloque después del seed.
INSERT IGNORE INTO `reserva_pedido` (`id_solicitud`, `nombre_material`, `cantidad`)
SELECT rm.`id_solicitud`, m.`nombre`, SUM(rm.`cantidad`)
  FROM `reserva_material` rm
  JOIN `material` m ON m.`id_material` = rm.`id_material`
 GROUP BY rm.`id_solicitud`, m.`nombre`;
