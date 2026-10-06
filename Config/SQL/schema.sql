-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- schema.sql v5 (Normalizado: minúsculas y sin caracteres especiales)
-- Motor: MySQL / MariaDB (InnoDB)
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
-- MATERIAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `material` (
  `id_material`        int(11) NOT NULL AUTO_INCREMENT,
  `nombre`             varchar(100) NOT NULL,
  `descripcion`        text DEFAULT NULL,
  `categoria`          varchar(50) DEFAULT NULL,
  `cantidad_total`     int(11) NOT NULL,
  `cantidad_disponible` int(11) NOT NULL,
  `estado`             varchar(30) DEFAULT NULL,
  `foto_material`      varchar(900) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- SOLICITUD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solicitud` (
  `id_solicitud`     int(11) NOT NULL AUTO_INCREMENT,
  `estado`           varchar(30) NOT NULL,
  `fecha_solicitud`  datetime NOT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo`   text DEFAULT NULL,
  `ci_cliente`       varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `ci_cliente` (`ci_cliente`),
  KEY `ci_administrador` (`ci_administrador`)
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
-- PRESTAMO_MATERIAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestamo_material` (
  `id_solicitud` int(11) NOT NULL,
  `id_material`  int(11) NOT NULL,
  `cantidad`     int(11) NOT NULL,
  PRIMARY KEY (`id_solicitud`, `id_material`),
  KEY `id_material` (`id_material`)
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
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_solicitud` (
  `id_historial`     int(11) NOT NULL AUTO_INCREMENT,
  `tipo`             varchar(50) DEFAULT NULL,
  `fecha_archivo`    datetime DEFAULT NULL,
  `estado`           varchar(30) DEFAULT NULL,
  `fecha_solicitud`  datetime DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo`   text DEFAULT NULL,
  `ci_cliente`       varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  KEY `ci_cliente` (`ci_cliente`),
  KEY `ci_administrador` (`ci_administrador`)
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
  ADD CONSTRAINT `fk_pm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE CASCADE;

ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud` (`id_solicitud`) ON DELETE CASCADE;

ALTER TABLE `reserva_material`
  ADD CONSTRAINT `fk_rm_reserva` FOREIGN KEY (`id_solicitud`) REFERENCES `reserva` (`id_solicitud`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE CASCADE;

ALTER TABLE `historial_solicitud`
  ADD CONSTRAINT `fk_historial_cliente` FOREIGN KEY (`ci_cliente`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_admin` FOREIGN KEY (`ci_administrador`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;