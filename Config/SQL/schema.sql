-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- schema.sql v7 (ID_serie en material, estado individual por unidad,
--                pedido por nombre, historial de estados del material)
-- Motor: MySQL 8.0.16+ / MariaDB 10.2.1+ (InnoDB)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuario` (
  `ci`           varchar(8)   NOT NULL,
  `nombre`       varchar(50)  NOT NULL,
  `apellido`     varchar(50)  NOT NULL,
  `contrasenia`  varchar(255) NOT NULL,
  `email`        varchar(100) NOT NULL,
  `rol`          varchar(20)  NOT NULL CHECK (`rol` IN ('ADMINISTRADOR', 'CLIENTE')),
  `especialidad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- TELEFONO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `telefono` (
  `ci_usuario` varchar(8)  NOT NULL,
  `telefono`   varchar(20) NOT NULL,
  PRIMARY KEY (`ci_usuario`, `telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- MATERIAL  (v7)
-- Cada fila es UNA de dos cosas, según tipo_control:
--   SERIE    -> un equipo físico identificado por id_serie
--               (cantidad_total = 1). Su estado es individual.
--   CANTIDAD -> una línea de consumibles sin número de serie
--               (cables, buzzers); se controla por cantidad.
-- Los equipos de un mismo tipo comparten el nombre: el catálogo
-- del cliente los agrupa por nombre.
--
-- estado (ver Config/Estados.php):
--   DISPONIBLE -> PRESTADO          (entrega de un préstamo; solo SERIE)
--   PRESTADO   -> DISPONIBLE        (devolución sin daños)
--   PRESTADO   -> EN_MANTENIMIENTO  (devolución con daños)
--   DISPONIBLE <-> EN_MANTENIMIENTO (administrador)
--   DISPONIBLE | EN_MANTENIMIENTO -> DADO_DE_BAJA (baja lógica, final)
-- «Agotado» no es un estado: se calcula con cantidad_disponible = 0.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `material` (
  `id_material`         int(11) NOT NULL AUTO_INCREMENT,
  `id_serie`            varchar(30) DEFAULT NULL,
  `tipo_control`        varchar(10) NOT NULL DEFAULT 'CANTIDAD'
                        CHECK (`tipo_control` IN ('SERIE', 'CANTIDAD')),
  `nombre`              varchar(100) NOT NULL,
  `descripcion`         text DEFAULT NULL,
  `categoria`           varchar(50) DEFAULT NULL,
  `cantidad_total`      int(11) NOT NULL,
  `cantidad_disponible` int(11) NOT NULL,
  `estado`              varchar(20) NOT NULL DEFAULT 'DISPONIBLE'
                        CHECK (`estado` IN ('DISPONIBLE', 'PRESTADO', 'EN_MANTENIMIENTO', 'DADO_DE_BAJA')),
  `foto_material`       varchar(900) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_baja`          datetime DEFAULT NULL,
  `motivo_baja`         text DEFAULT NULL,
  PRIMARY KEY (`id_material`),
  UNIQUE KEY `uq_material_serie` (`id_serie`),
  KEY `idx_material_nombre_estado` (`nombre`, `estado`),
  -- SERIE: tiene serie y es una sola unidad; CANTIDAD: no tiene serie
  CONSTRAINT `chk_material_tipo` CHECK (
        (`tipo_control` = 'SERIE'    AND `id_serie` IS NOT NULL AND `cantidad_total` = 1)
     OR (`tipo_control` = 'CANTIDAD' AND `id_serie` IS NULL)),
  CONSTRAINT `chk_material_cantidades` CHECK (
        `cantidad_total` >= 0 AND `cantidad_disponible` BETWEEN 0 AND `cantidad_total`),
  -- Una línea de consumibles nunca está PRESTADA como un todo
  CONSTRAINT `chk_material_prestado` CHECK (
        `estado` <> 'PRESTADO' OR `tipo_control` = 'SERIE'),
  -- En un equipo, el disponible acompaña al estado
  CONSTRAINT `chk_material_serie_disp` CHECK (
        `tipo_control` = 'CANTIDAD'
     OR `cantidad_disponible` = (CASE WHEN `estado` = 'DISPONIBLE' THEN 1 ELSE 0 END)),
  -- La baja lógica guarda cuándo se hizo
  CONSTRAINT `chk_material_baja` CHECK (
        (`estado` = 'DADO_DE_BAJA') = (`fecha_baja` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- HISTORIAL_ESTADO_MATERIAL  (v7, nueva)
-- Una fila por cada cambio de estado de un material: quién, cuándo,
-- por qué y, si corresponde, en qué préstamo. Es la traza que pide
-- RNF7 y la tabla donde se evalúa el estado de cada unidad.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_estado_material` (
  `id_cambio`       int(11) NOT NULL AUTO_INCREMENT,
  `id_material`     int(11) NOT NULL,
  `estado_anterior` varchar(20) DEFAULT NULL
                    CHECK (`estado_anterior` IS NULL OR `estado_anterior` IN ('DISPONIBLE', 'PRESTADO', 'EN_MANTENIMIENTO', 'DADO_DE_BAJA')),
  `estado_nuevo`    varchar(20) NOT NULL
                    CHECK (`estado_nuevo` IN ('DISPONIBLE', 'PRESTADO', 'EN_MANTENIMIENTO', 'DADO_DE_BAJA')),
  `evento`          varchar(30) NOT NULL,
  `fecha`           datetime NOT NULL,
  `ci_responsable`  varchar(8) DEFAULT NULL,
  `id_solicitud`    int(11) DEFAULT NULL,
  `motivo`          text DEFAULT NULL,
  PRIMARY KEY (`id_cambio`),
  KEY `idx_hem_material` (`id_material`, `fecha`),
  KEY `ci_responsable` (`ci_responsable`),
  KEY `id_solicitud` (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- SOLICITUD
-- estado: PENDIENTE -> APROBADO | RECHAZADO | CANCELADO
--         APROBADO  -> ACTIVO (entrega) | CANCELADO
--         ACTIVO    -> FINALIZADO (devolución de todo lo prestado)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solicitud` (
  `id_solicitud`     int(11) NOT NULL AUTO_INCREMENT,
  `estado`           varchar(30) NOT NULL DEFAULT 'PENDIENTE'
                     CHECK (`estado` IN ('PENDIENTE', 'APROBADO', 'RECHAZADO', 'ACTIVO', 'FINALIZADO', 'CANCELADO')),
  `fecha_solicitud`  datetime NOT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo`   text DEFAULT NULL,
  `ci_cliente`       varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `ci_cliente` (`ci_cliente`),
  KEY `ci_administrador` (`ci_administrador`),
  KEY `idx_solicitud_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestamo` (
  `id_solicitud`      int(11) NOT NULL,
  `materia`           varchar(100) DEFAULT NULL,
  `horas_solicitadas` int(11) DEFAULT NULL,
  `fecha_entrega`     datetime DEFAULT NULL,
  `fecha_devolucion`  datetime DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO_PEDIDO  (v7, nueva)
-- Lo que el cliente pidió, por nombre de material y cantidad
-- («2 Ceibalitas»). Todavía no hay unidades asignadas.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestamo_pedido` (
  `id_solicitud`    int(11) NOT NULL,
  `nombre_material` varchar(100) NOT NULL,
  `cantidad`        int(11) NOT NULL CHECK (`cantidad` > 0),
  PRIMARY KEY (`id_solicitud`, `nombre_material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO_MATERIAL  (v7)
-- Lo que efectivamente se entregó. La completa el administrador
-- al entregar: una fila por cada equipo (SERIE, cantidad = 1) o
-- por cada línea de consumibles (CANTIDAD). Al devolver se anota
-- la fecha, cuánto volvió y en qué estado quedó el equipo.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestamo_material` (
  `id_solicitud`      int(11) NOT NULL,
  `id_material`       int(11) NOT NULL,
  `cantidad`          int(11) NOT NULL CHECK (`cantidad` > 0),
  `fecha_devolucion`  datetime DEFAULT NULL,
  `cantidad_devuelta` int(11) DEFAULT NULL,
  `estado_devolucion` varchar(20) DEFAULT NULL
                      CHECK (`estado_devolucion` IS NULL OR `estado_devolucion` IN ('DISPONIBLE', 'EN_MANTENIMIENTO')),
  `observacion`       text DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`, `id_material`),
  KEY `id_material` (`id_material`),
  CONSTRAINT `chk_pm_devolucion` CHECK (
        `cantidad_devuelta` IS NULL OR `cantidad_devuelta` BETWEEN 0 AND `cantidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- RESERVA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reserva` (
  `id_solicitud` int(11) NOT NULL,
  `fecha`        date DEFAULT NULL,
  `hora_inicio`  time DEFAULT NULL,
  `hora_fin`     time DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- RESERVA_MATERIAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reserva_material` (
  `id_solicitud` int(11) NOT NULL,
  `id_material`  int(11) NOT NULL,
  `cantidad`     int(11) NOT NULL,
  PRIMARY KEY (`id_solicitud`, `id_material`),
  KEY `id_material` (`id_material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- (estado puede ser NULL; si tiene valor, debe ser uno válido)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_solicitud` (
  `id_historial`     int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud_origen` int(11) DEFAULT NULL,  -- v7: solicitud archivada
  `tipo`             varchar(50) DEFAULT NULL,
  `fecha_archivo`    datetime DEFAULT NULL,
  `estado`           varchar(30) DEFAULT NULL
                     CHECK (`estado` IS NULL OR `estado` IN ('PENDIENTE', 'APROBADO', 'RECHAZADO', 'ACTIVO', 'FINALIZADO', 'CANCELADO')),
  `fecha_solicitud`  datetime DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo`   text DEFAULT NULL,
  `ci_cliente`       varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `ci_cliente` (`ci_cliente`),
  KEY `ci_administrador` (`ci_administrador`),
  UNIQUE KEY `uq_historial_origen` (`id_solicitud_origen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Restricciones (Foreign Keys)
-- ============================================================
ALTER TABLE `telefono`
  ADD CONSTRAINT `fk_telefono_usuario` FOREIGN KEY (`ci_usuario`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `solicitud`
  ADD CONSTRAINT `fk_solicitud_cliente` FOREIGN KEY (`ci_cliente`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitud_admin` FOREIGN KEY (`ci_administrador`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `prestamo`
  ADD CONSTRAINT `fk_prestamo_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud` (`id_solicitud`) ON DELETE CASCADE;

ALTER TABLE `prestamo_material`
  ADD CONSTRAINT `fk_pm_prestamo` FOREIGN KEY (`id_solicitud`) REFERENCES `prestamo` (`id_solicitud`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE RESTRICT;

ALTER TABLE `prestamo_pedido`
  ADD CONSTRAINT `fk_pp_prestamo` FOREIGN KEY (`id_solicitud`) REFERENCES `prestamo` (`id_solicitud`) ON DELETE CASCADE;

ALTER TABLE `historial_estado_material`
  ADD CONSTRAINT `fk_hem_material`    FOREIGN KEY (`id_material`)    REFERENCES `material` (`id_material`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_hem_responsable` FOREIGN KEY (`ci_responsable`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hem_solicitud`   FOREIGN KEY (`id_solicitud`)   REFERENCES `solicitud` (`id_solicitud`) ON DELETE SET NULL;

ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud` (`id_solicitud`) ON DELETE CASCADE;

ALTER TABLE `reserva_material`
  ADD CONSTRAINT `fk_rm_reserva` FOREIGN KEY (`id_solicitud`) REFERENCES `reserva` (`id_solicitud`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE RESTRICT;

ALTER TABLE `historial_solicitud`
  ADD CONSTRAINT `fk_historial_cliente` FOREIGN KEY (`ci_cliente`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_admin` FOREIGN KEY (`ci_administrador`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;