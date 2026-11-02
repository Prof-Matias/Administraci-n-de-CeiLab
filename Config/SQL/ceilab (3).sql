-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 29-10-2026 a las 14:04:13
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
-- Estructura de tabla para la tabla `historial_estado_material`
--

DROP TABLE IF EXISTS `historial_estado_material`;
CREATE TABLE IF NOT EXISTS `historial_estado_material` (
  `id_cambio` int NOT NULL AUTO_INCREMENT,
  `id_material` int NOT NULL,
  `estado_anterior` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_nuevo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `evento` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` datetime NOT NULL,
  `ci_responsable` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_solicitud` int DEFAULT NULL,
  `motivo` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_cambio`),
  KEY `idx_hem_material` (`id_material`,`fecha`),
  KEY `ci_responsable` (`ci_responsable`),
  KEY `id_solicitud` (`id_solicitud`)
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_solicitud`
--

DROP TABLE IF EXISTS `historial_solicitud`;
CREATE TABLE IF NOT EXISTS `historial_solicitud` (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `id_solicitud_origen` int DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_archivo` datetime DEFAULT NULL,
  `estado` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo` text COLLATE utf8mb4_general_ci,
  `ci_cliente` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `ci_administrador` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_historial`),
  UNIQUE KEY `uq_historial_origen` (`id_solicitud_origen`),
  KEY `ci_cliente` (`ci_cliente`),
  KEY `ci_administrador` (`ci_administrador`)
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `material`
--

DROP TABLE IF EXISTS `material`;
CREATE TABLE IF NOT EXISTS `material` (
  `id_material` int NOT NULL AUTO_INCREMENT,
  `id_serie` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_control` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'CANTIDAD',
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `categoria` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad_total` int NOT NULL,
  `cantidad_disponible` int NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DISPONIBLE',
  `foto_material` varchar(900) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_baja` datetime DEFAULT NULL,
  `motivo_baja` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_material`),
  UNIQUE KEY `uq_material_serie` (`id_serie`),
  KEY `idx_material_nombre_estado` (`nombre`,`estado`)
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo`
--

DROP TABLE IF EXISTS `prestamo`;
CREATE TABLE IF NOT EXISTS `prestamo` (
  `id_solicitud` int NOT NULL,
  `materia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `horas_solicitadas` int DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `fecha_devolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_material`
--

DROP TABLE IF EXISTS `prestamo_material`;
CREATE TABLE IF NOT EXISTS `prestamo_material` (
  `id_solicitud` int NOT NULL,
  `id_material` int NOT NULL,
  `cantidad` int NOT NULL,
  `fecha_devolucion` datetime DEFAULT NULL,
  `cantidad_devuelta` int DEFAULT NULL,
  `estado_devolucion` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacion` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_solicitud`,`id_material`),
  KEY `id_material` (`id_material`)
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_pedido`
--

DROP TABLE IF EXISTS `prestamo_pedido`;
CREATE TABLE IF NOT EXISTS `prestamo_pedido` (
  `id_solicitud` int NOT NULL,
  `nombre_material` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` int NOT NULL,
  PRIMARY KEY (`id_solicitud`,`nombre_material`)
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

DROP TABLE IF EXISTS `reserva`;
CREATE TABLE IF NOT EXISTS `reserva` (
  `id_solicitud` int NOT NULL,
  `fecha` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_material`
--

DROP TABLE IF EXISTS `reserva_material`;
CREATE TABLE IF NOT EXISTS `reserva_material` (
  `id_solicitud` int NOT NULL,
  `id_material` int NOT NULL,
  `cantidad` int NOT NULL,
  PRIMARY KEY (`id_solicitud`,`id_material`),
  KEY `id_material` (`id_material`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud`
--

DROP TABLE IF EXISTS `solicitud`;
CREATE TABLE IF NOT EXISTS `solicitud` (
  `id_solicitud` int NOT NULL AUTO_INCREMENT,
  `estado` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `fecha_solicitud` datetime NOT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo` text COLLATE utf8mb4_general_ci,
  `ci_cliente` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `ci_administrador` varchar(8) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `ci_cliente` (`ci_cliente`),
  KEY `ci_administrador` (`ci_administrador`),
  KEY `idx_solicitud_estado` (`estado`)
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

DROP TABLE IF EXISTS `telefono`;
CREATE TABLE IF NOT EXISTS `telefono` (
  `ci_usuario` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`ci_usuario`,`telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `ci` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `contrasenia` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_cuenta` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (`ci`)
) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_estado_material`
--
ALTER TABLE `historial_estado_material`
  ADD CONSTRAINT `fk_hem_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_hem_responsable` FOREIGN KEY (`ci_responsable`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hem_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud` (`id_solicitud`) ON DELETE SET NULL;

--
-- Filtros para la tabla `historial_solicitud`
--
ALTER TABLE `historial_solicitud`
  ADD CONSTRAINT `fk_historial_admin` FOREIGN KEY (`ci_administrador`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_cliente` FOREIGN KEY (`ci_cliente`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD CONSTRAINT `fk_prestamo_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `prestamo_material`
--
ALTER TABLE `prestamo_material`
  ADD CONSTRAINT `fk_pm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_pm_prestamo` FOREIGN KEY (`id_solicitud`) REFERENCES `prestamo` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `prestamo_pedido`
--
ALTER TABLE `prestamo_pedido`
  ADD CONSTRAINT `fk_pp_prestamo` FOREIGN KEY (`id_solicitud`) REFERENCES `prestamo` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `fk_reserva_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reserva_material`
--
ALTER TABLE `reserva_material`
  ADD CONSTRAINT `fk_rm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_rm_reserva` FOREIGN KEY (`id_solicitud`) REFERENCES `reserva` (`id_solicitud`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitud`
--
ALTER TABLE `solicitud`
  ADD CONSTRAINT `fk_solicitud_admin` FOREIGN KEY (`ci_administrador`) REFERENCES `usuario` (`ci`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_solicitud_cliente` FOREIGN KEY (`ci_cliente`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `telefono`
--
ALTER TABLE `telefono`
  ADD CONSTRAINT `fk_telefono_usuario` FOREIGN KEY (`ci_usuario`) REFERENCES `usuario` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
