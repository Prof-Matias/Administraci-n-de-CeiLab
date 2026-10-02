-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- schema.sql v4
-- Motor: MySQL / MariaDB (InnoDB)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuario` (
  `CI`           varchar(8)   NOT NULL,
  `Nombre`       varchar(50)  NOT NULL,
  `Apellido`     varchar(50)  NOT NULL,
  `Contraseña`   varchar(255) NOT NULL,
  `Email`        varchar(100) NOT NULL,
  `Rol`          varchar(20)  NOT NULL CHECK (`Rol` IN ('ADMINISTRADOR', 'CLIENTE')),
  `Especialidad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`CI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- TELEFONO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `telefono` (
  `CI_Usuario` varchar(8)  NOT NULL,
  `Telefono`   varchar(20) NOT NULL,
  PRIMARY KEY (`CI_Usuario`, `Telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- MATERIAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `material` (
  `ID_Material`        int(11) NOT NULL AUTO_INCREMENT,
  `Nombre`             varchar(100) NOT NULL,
  `Descripcion`        text DEFAULT NULL,
  `Categoria`          varchar(50) DEFAULT NULL,
  `Cantidad_Total`      int(11) NOT NULL,
  `Cantidad_Disponible` int(11) NOT NULL,
  `Estado`             varchar(30) DEFAULT NULL,
  `Foto_Material`      varchar(900) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`ID_Material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- SOLICITUD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solicitud` (
  `ID_Solicitud`     int(11) NOT NULL AUTO_INCREMENT,
  `Estado`           varchar(30) NOT NULL,
  `Fecha_Solicitud`  datetime NOT NULL,
  `Fecha_Validacion` datetime DEFAULT NULL,
  `Motivo_Rechazo`   text DEFAULT NULL,
  `CI_Cliente`       varchar(8) NOT NULL,
  `CI_Administrador` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`),
  KEY `CI_Cliente` (`CI_Cliente`),
  KEY `CI_Administrador` (`CI_Administrador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestamo` (
  `ID_Solicitud`      int(11) NOT NULL,
  `Materia`           varchar(100) DEFAULT NULL,
  `Horas_Solicitadas` int(11) DEFAULT NULL,
  `Fecha_Entrega`     datetime DEFAULT NULL,
  `Fecha_Devolucion`  datetime DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO_MATERIAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prestamo_material` (
  `ID_Solicitud` int(11) NOT NULL,
  `ID_Material`  int(11) NOT NULL,
  `Cantidad`     int(11) NOT NULL,
  PRIMARY KEY (`ID_Solicitud`, `ID_Material`),
  KEY `ID_Material` (`ID_Material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- RESERVA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reserva` (
  `ID_Solicitud` int(11) NOT NULL,
  `Fecha`        date DEFAULT NULL,
  `Hora_Inicio`  time DEFAULT NULL,
  `Hora_Fin`     time DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- RESERVA_MATERIAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reserva_material` (
  `ID_Solicitud` int(11) NOT NULL,
  `ID_Material`  int(11) NOT NULL,
  `Cantidad`     int(11) NOT NULL,
  PRIMARY KEY (`ID_Solicitud`, `ID_Material`),
  KEY `ID_Material` (`ID_Material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `historial_solicitud` (
  `ID_Historial`     int(11) NOT NULL AUTO_INCREMENT,
  `Tipo`             varchar(50) DEFAULT NULL,
  `Fecha_Archivo`    datetime DEFAULT NULL,
  `Estado`           varchar(30) DEFAULT NULL,
  `Fecha_Solicitud`  datetime DEFAULT NULL,
  `Fecha_Validacion` datetime DEFAULT NULL,
  `Motivo_Rechazo`   text DEFAULT NULL,
  `CI_Cliente`       varchar(8) NOT NULL,
  `CI_Administrador` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`ID_Historial`),
  KEY `CI_Cliente` (`CI_Cliente`),
  KEY `CI_Administrador` (`CI_Administrador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Restricciones (Foreign Keys)
-- ============================================================
ALTER TABLE `telefono`
  ADD CONSTRAINT `telefono_ibfk_1` FOREIGN KEY (`CI_Usuario`) REFERENCES `usuario` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `solicitud`
  ADD CONSTRAINT `solicitud_ibfk_1` FOREIGN KEY (`CI_Cliente`) REFERENCES `usuario` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `solicitud_ibfk_2` FOREIGN KEY (`CI_Administrador`) REFERENCES `usuario` (`CI`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `prestamo`
  ADD CONSTRAINT `prestamo_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `solicitud` (`ID_Solicitud`) ON DELETE CASCADE;

ALTER TABLE `prestamo_material`
  ADD CONSTRAINT `prestamo_material_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `prestamo` (`ID_Solicitud`) ON DELETE CASCADE,
  ADD CONSTRAINT `prestamo_material_ibfk_2` FOREIGN KEY (`ID_Material`) REFERENCES `material` (`ID_Material`) ON DELETE CASCADE;

ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `solicitud` (`ID_Solicitud`) ON DELETE CASCADE;

ALTER TABLE `reserva_material`
  ADD CONSTRAINT `reserva_material_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `reserva` (`ID_Solicitud`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserva_material_ibfk_2` FOREIGN KEY (`ID_Material`) REFERENCES `material` (`ID_Material`) ON DELETE CASCADE;

ALTER TABLE `historial_solicitud`
  ADD CONSTRAINT `historial_solicitud_ibfk_1` FOREIGN KEY (`CI_Cliente`) REFERENCES `usuario` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `historial_solicitud_ibfk_2` FOREIGN KEY (`CI_Administrador`) REFERENCES `usuario` (`CI`) ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;