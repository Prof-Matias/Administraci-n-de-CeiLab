-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- schema.sql v2 — Usuario fusionado (sin Administrador/Cliente)
-- Motor: MySQL / MariaDB (InnoDB)
-- Para importar en una base NUEVA (sin tablas previas)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIO
-- Rol distingue Administrador vs Cliente (los 2 checkbox del
-- formulario de registro). Especialidad solo se completa cuando
-- Rol = 'CLIENTE' (se desbloquea en el formulario, se valida en
-- el código; la BD la permite NULL siempre).
-- ------------------------------------------------------------
CREATE TABLE `usuario` (
  `CI`          varchar(8)   NOT NULL,
  `Nombre`      varchar(50)  NOT NULL,
  `Apellido`    varchar(50)  NOT NULL,
  `Contraseña`  varchar(255) NOT NULL,
  `Email`       varchar(100) NOT NULL,
  `Rol`         varchar(20)  NOT NULL CHECK (`Rol` IN ('ADMINISTRADOR', 'CLIENTE')),
  `Especialidad` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- TELEFONO
-- ------------------------------------------------------------
CREATE TABLE `telefono` (
  `CI_Usuario` varchar(8)  NOT NULL,
  `Telefono`   varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- MATERIAL
-- ------------------------------------------------------------
CREATE TABLE `material` (
  `ID_Material`         int(11) NOT NULL,
  `Nombre`              varchar(100) NOT NULL,
  `Descripcion`         text DEFAULT NULL,
  `Categoria`           varchar(50) DEFAULT NULL,
  `Cantidad_Total`      int(11) NOT NULL,
  `Cantidad_Disponible` int(11) NOT NULL,
  `Estado`              varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- SOLICITUD
-- ------------------------------------------------------------
CREATE TABLE `solicitud` (
  `ID_Solicitud`     int(11) NOT NULL,
  `Estado`           varchar(30) NOT NULL,
  `Fecha_Solicitud`  datetime NOT NULL,
  `Fecha_Validacion` datetime DEFAULT NULL,
  `Motivo_Rechazo`   text DEFAULT NULL,
  `CI_Cliente`       varchar(8) NOT NULL,
  `CI_Administrador` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO
-- ------------------------------------------------------------
CREATE TABLE `prestamo` (
  `ID_Solicitud`      int(11) NOT NULL,
  `Materia`           varchar(100) DEFAULT NULL,
  `Horas_Solicitadas` int(11) DEFAULT NULL,
  `Fecha_Entrega`     datetime DEFAULT NULL,
  `Fecha_Devolucion`  datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- PRESTAMO_MATERIAL
-- ------------------------------------------------------------
CREATE TABLE `prestamo_material` (
  `ID_Solicitud` int(11) NOT NULL,
  `ID_Material`  int(11) NOT NULL,
  `Cantidad`     int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- RESERVA
-- ------------------------------------------------------------
CREATE TABLE `reserva` (
  `ID_Solicitud` int(11) NOT NULL,
  `Fecha`        date DEFAULT NULL,
  `Hora_Inicio`  time DEFAULT NULL,
  `Hora_Fin`     time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- RESERVA_MATERIAL
-- ------------------------------------------------------------
CREATE TABLE `reserva_material` (
  `ID_Solicitud` int(11) NOT NULL,
  `ID_Material`  int(11) NOT NULL,
  `Cantidad`     int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- ------------------------------------------------------------
CREATE TABLE `historial_solicitud` (
  `ID_Historial`     int(11) NOT NULL,
  `Tipo`             varchar(50) DEFAULT NULL,
  `Fecha_Archivo`    datetime DEFAULT NULL,
  `Estado`           varchar(30) DEFAULT NULL,
  `Fecha_Solicitud`  datetime DEFAULT NULL,
  `Fecha_Validacion` datetime DEFAULT NULL,
  `Motivo_Rechazo`   text DEFAULT NULL,
  `CI_Cliente`       varchar(8) DEFAULT NULL,
  `CI_Administrador` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Índices
-- ============================================================
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`CI`);

ALTER TABLE `telefono`
  ADD PRIMARY KEY (`CI_Usuario`, `Telefono`);

ALTER TABLE `material`
  ADD PRIMARY KEY (`ID_Material`);

ALTER TABLE `solicitud`
  ADD PRIMARY KEY (`ID_Solicitud`),
  ADD KEY `CI_Cliente` (`CI_Cliente`),
  ADD KEY `CI_Administrador` (`CI_Administrador`);

ALTER TABLE `prestamo`
  ADD PRIMARY KEY (`ID_Solicitud`);

ALTER TABLE `prestamo_material`
  ADD PRIMARY KEY (`ID_Solicitud`, `ID_Material`),
  ADD KEY `ID_Material` (`ID_Material`);

ALTER TABLE `reserva`
  ADD PRIMARY KEY (`ID_Solicitud`);

ALTER TABLE `reserva_material`
  ADD PRIMARY KEY (`ID_Solicitud`, `ID_Material`),
  ADD KEY `ID_Material` (`ID_Material`);

ALTER TABLE `historial_solicitud`
  ADD PRIMARY KEY (`ID_Historial`),
  ADD KEY `CI_Cliente` (`CI_Cliente`),
  ADD KEY `CI_Administrador` (`CI_Administrador`);

-- ============================================================
-- Restricciones (Foreign Keys)
-- ============================================================
ALTER TABLE `telefono`
  ADD CONSTRAINT `telefono_ibfk_1` FOREIGN KEY (`CI_Usuario`) REFERENCES `usuario` (`CI`);

ALTER TABLE `solicitud`
  ADD CONSTRAINT `solicitud_ibfk_1` FOREIGN KEY (`CI_Cliente`) REFERENCES `usuario` (`CI`),
  ADD CONSTRAINT `solicitud_ibfk_2` FOREIGN KEY (`CI_Administrador`) REFERENCES `usuario` (`CI`);

ALTER TABLE `prestamo`
  ADD CONSTRAINT `prestamo_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `solicitud` (`ID_Solicitud`);

ALTER TABLE `prestamo_material`
  ADD CONSTRAINT `prestamo_material_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `prestamo` (`ID_Solicitud`),
  ADD CONSTRAINT `prestamo_material_ibfk_2` FOREIGN KEY (`ID_Material`) REFERENCES `material` (`ID_Material`);

ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `solicitud` (`ID_Solicitud`);

ALTER TABLE `reserva_material`
  ADD CONSTRAINT `reserva_material_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `reserva` (`ID_Solicitud`),
  ADD CONSTRAINT `reserva_material_ibfk_2` FOREIGN KEY (`ID_Material`) REFERENCES `material` (`ID_Material`);

ALTER TABLE `historial_solicitud`
  ADD CONSTRAINT `historial_solicitud_ibfk_1` FOREIGN KEY (`CI_Cliente`) REFERENCES `usuario` (`CI`),
  ADD CONSTRAINT `historial_solicitud_ibfk_2` FOREIGN KEY (`CI_Administrador`) REFERENCES `usuario` (`CI`);

SET FOREIGN_KEY_CHECKS = 1;
