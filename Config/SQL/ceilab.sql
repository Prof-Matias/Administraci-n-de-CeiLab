-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 29-09-2026 a las 23:46:43
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ceilab`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_solicitud`
--

DROP TABLE IF EXISTS `historial_solicitud`;
CREATE TABLE IF NOT EXISTS `historial_solicitud` (
  `ID_Historial` int NOT NULL,
  `Tipo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Fecha_Archivo` datetime DEFAULT NULL,
  `Estado` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Fecha_Solicitud` datetime DEFAULT NULL,
  `Fecha_Validacion` datetime DEFAULT NULL,
  `Motivo_Rechazo` text COLLATE utf8mb4_general_ci,
  `CI_Cliente` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CI_Administrador` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID_Historial`),
  KEY `CI_Cliente` (`CI_Cliente`),
  KEY `CI_Administrador` (`CI_Administrador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_solicitud`
--

INSERT INTO `historial_solicitud` (`ID_Historial`, `Tipo`, `Fecha_Archivo`, `Estado`, `Fecha_Solicitud`, `Fecha_Validacion`, `Motivo_Rechazo`, `CI_Cliente`, `CI_Administrador`) VALUES
(1, 'Prestamo', '2026-08-20 16:00:00', 'Aprobado', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, 'Reserva', '2026-08-25 12:00:00', 'Aprobado', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `material`
--

DROP TABLE IF EXISTS `material`;
CREATE TABLE IF NOT EXISTS `material` (
  `ID_Material` int NOT NULL,
  `Nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Descripcion` text COLLATE utf8mb4_general_ci,
  `Categoria` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Cantidad_Total` int NOT NULL,
  `Cantidad_Disponible` int NOT NULL,
  `Estado` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Foto_Material` varchar(900) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`ID_Material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `material`
--

INSERT INTO `material` (`ID_Material`, `Nombre`, `Descripcion`, `Categoria`, `Cantidad_Total`, `Cantidad_Disponible`, `Estado`, `Foto_Material`) VALUES
(1, 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 10, 7, 'Disponible', ''),
(2, 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 6, 4, 'Disponible', ''),
(3, 'Dron', 'Dron para programación de vuelo', 'Robótica y Programación', 2, 0, 'Reservado', ''),
(4, 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 4, 4, 'Disponible', ''),
(5, 'Impresora 3D', 'Impresora 3D del laboratorio', 'Robótica y Programación', 1, 1, 'Disponible', ''),
(6, 'Cable pinza cocodrilo', 'Cable con pinzas cocodrilo', 'Gadgets', 20, 18, 'Disponible', ''),
(7, 'Buzzer', 'Buzzer para prácticas con micro:bit', 'Gadgets', 15, 15, 'Disponible', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo`
--

DROP TABLE IF EXISTS `prestamo`;
CREATE TABLE IF NOT EXISTS `prestamo` (
  `ID_Solicitud` int NOT NULL,
  `Materia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Horas_Solicitadas` int DEFAULT NULL,
  `Fecha_Entrega` datetime DEFAULT NULL,
  `Fecha_Devolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prestamo`
--

INSERT INTO `prestamo` (`ID_Solicitud`, `Materia`, `Horas_Solicitadas`, `Fecha_Entrega`, `Fecha_Devolucion`) VALUES
(1, 'Física', 4, NULL, NULL),
(2, 'Matemática', 8, '2026-09-10 09:00:00', NULL),
(3, 'Química', 2, NULL, NULL),
(8, 'Informática', 6, '2026-09-15 09:00:00', '2026-09-15 15:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_material`
--

DROP TABLE IF EXISTS `prestamo_material`;
CREATE TABLE IF NOT EXISTS `prestamo_material` (
  `ID_Solicitud` int NOT NULL,
  `ID_Material` int NOT NULL,
  `Cantidad` int NOT NULL,
  PRIMARY KEY (`ID_Solicitud`,`ID_Material`),
  KEY `ID_Material` (`ID_Material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prestamo_material`
--

INSERT INTO `prestamo_material` (`ID_Solicitud`, `ID_Material`, `Cantidad`) VALUES
(1, 1, 1),
(1, 6, 2),
(2, 1, 1),
(3, 2, 1),
(8, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

DROP TABLE IF EXISTS `reserva`;
CREATE TABLE IF NOT EXISTS `reserva` (
  `ID_Solicitud` int NOT NULL,
  `Fecha` date DEFAULT NULL,
  `Hora_Inicio` time DEFAULT NULL,
  `Hora_Fin` time DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`ID_Solicitud`, `Fecha`, `Hora_Inicio`, `Hora_Fin`) VALUES
(4, '2026-10-05', '14:00:00', '16:00:00'),
(5, '2026-10-03', '10:00:00', '12:00:00'),
(6, '2026-10-03', '11:00:00', '13:00:00'),
(7, '2026-09-22', '09:00:00', '10:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_material`
--

DROP TABLE IF EXISTS `reserva_material`;
CREATE TABLE IF NOT EXISTS `reserva_material` (
  `ID_Solicitud` int NOT NULL,
  `ID_Material` int NOT NULL,
  `Cantidad` int NOT NULL,
  PRIMARY KEY (`ID_Solicitud`,`ID_Material`),
  KEY `ID_Material` (`ID_Material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reserva_material`
--

INSERT INTO `reserva_material` (`ID_Solicitud`, `ID_Material`, `Cantidad`) VALUES
(4, 4, 2),
(5, 5, 1),
(6, 5, 1),
(7, 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud`
--

DROP TABLE IF EXISTS `solicitud`;
CREATE TABLE IF NOT EXISTS `solicitud` (
  `ID_Solicitud` int NOT NULL,
  `Estado` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `Fecha_Solicitud` datetime NOT NULL,
  `Fecha_Validacion` datetime DEFAULT NULL,
  `Motivo_Rechazo` text COLLATE utf8mb4_general_ci,
  `CI_Cliente` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `CI_Administrador` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`),
  KEY `CI_Cliente` (`CI_Cliente`),
  KEY `CI_Administrador` (`CI_Administrador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitud`
--

INSERT INTO `solicitud` (`ID_Solicitud`, `Estado`, `Fecha_Solicitud`, `Fecha_Validacion`, `Motivo_Rechazo`, `CI_Cliente`, `CI_Administrador`) VALUES
(1, 'Pendiente', '2026-09-25 09:00:00', NULL, NULL, '20000001', NULL),
(2, 'Aprobado', '2026-09-08 08:30:00', '2026-09-08 09:00:00', NULL, '20000002', '10000001'),
(3, 'Rechazado', '2026-09-20 10:00:00', '2026-09-20 11:00:00', 'Cédula con préstamos vencidos', '20000003', '10000002'),
(4, 'Pendiente', '2026-09-26 15:00:00', NULL, NULL, '20000004', NULL),
(5, 'Aprobado', '2026-09-24 09:00:00', '2026-09-24 10:00:00', NULL, '20000001', '10000001'),
(6, 'Pendiente', '2026-09-26 16:00:00', NULL, NULL, '20000002', NULL),
(7, 'Rechazado', '2026-09-22 09:00:00', '2026-09-22 12:00:00', 'Horario en conflicto con otra reserva', '20000003', '10000002'),
(8, 'Aprobado', '2026-09-14 08:00:00', '2026-09-14 08:30:00', NULL, '20000004', '10000001');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

DROP TABLE IF EXISTS `telefono`;
CREATE TABLE IF NOT EXISTS `telefono` (
  `CI_Usuario` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `Telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`CI_Usuario`,`Telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `telefono`
--

INSERT INTO `telefono` (`CI_Usuario`, `Telefono`) VALUES
('10000001', '099111111'),
('20000001', '099222222'),
('20000001', '099222233'),
('20000002', '099333333');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `CI` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `Nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `Apellido` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `Contraseña` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Rol` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `Especialidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`CI`)
) ;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`CI`, `Nombre`, `Apellido`, `Contraseña`, `Email`, `Rol`, `Especialidad`) VALUES
('10000001', 'Lucía', 'Fernández', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'lucia.fernandez@cerpdeleste.edu.uy', 'ADMINISTRADOR', NULL),
('10000002', 'Martín', 'Rodríguez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'martin.rodriguez@cerpdeleste.edu.uy', 'ADMINISTRADOR', NULL),
('20000001', 'Ana', 'Pereira', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'ana.pereira@cerpdeleste.edu.uy', 'CLIENTE', NULL),
('20000002', 'Carlos', 'Gómez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'carlos.gomez@cerpdeleste.edu.uy', 'CLIENTE', 'Matemática'),
('20000003', 'Valentina', 'Silva', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'valentina.silva@cerpdeleste.edu.uy', 'CLIENTE', 'Informática'),
('20000004', 'Diego', 'Martínez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'diego.martinez@cerpdeleste.edu.uy', 'CLIENTE', 'Informática');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_solicitud`
--
ALTER TABLE `historial_solicitud`
  ADD CONSTRAINT `historial_solicitud_ibfk_1` FOREIGN KEY (`CI_Cliente`) REFERENCES `usuario` (`CI`),
  ADD CONSTRAINT `historial_solicitud_ibfk_2` FOREIGN KEY (`CI_Administrador`) REFERENCES `usuario` (`CI`);

--
-- Filtros para la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD CONSTRAINT `prestamo_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `solicitud` (`ID_Solicitud`);

--
-- Filtros para la tabla `prestamo_material`
--
ALTER TABLE `prestamo_material`
  ADD CONSTRAINT `prestamo_material_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `prestamo` (`ID_Solicitud`),
  ADD CONSTRAINT `prestamo_material_ibfk_2` FOREIGN KEY (`ID_Material`) REFERENCES `material` (`ID_Material`);

--
-- Filtros para la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `solicitud` (`ID_Solicitud`);

--
-- Filtros para la tabla `reserva_material`
--
ALTER TABLE `reserva_material`
  ADD CONSTRAINT `reserva_material_ibfk_1` FOREIGN KEY (`ID_Solicitud`) REFERENCES `reserva` (`ID_Solicitud`),
  ADD CONSTRAINT `reserva_material_ibfk_2` FOREIGN KEY (`ID_Material`) REFERENCES `material` (`ID_Material`);

--
-- Filtros para la tabla `solicitud`
--
ALTER TABLE `solicitud`
  ADD CONSTRAINT `solicitud_ibfk_1` FOREIGN KEY (`CI_Cliente`) REFERENCES `usuario` (`CI`),
  ADD CONSTRAINT `solicitud_ibfk_2` FOREIGN KEY (`CI_Administrador`) REFERENCES `usuario` (`CI`);

--
-- Filtros para la tabla `telefono`
--
ALTER TABLE `telefono`
  ADD CONSTRAINT `telefono_ibfk_1` FOREIGN KEY (`CI_Usuario`) REFERENCES `usuario` (`CI`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
