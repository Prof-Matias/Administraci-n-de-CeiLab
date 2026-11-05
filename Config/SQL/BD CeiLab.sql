-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2026 a las 03:25:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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

CREATE TABLE `historial_estado_material` (
  `id_cambio` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `estado_anterior` varchar(20) DEFAULT NULL,
  `estado_nuevo` varchar(20) NOT NULL,
  `evento` varchar(30) NOT NULL,
  `fecha` datetime NOT NULL,
  `ci_responsable` varchar(8) DEFAULT NULL,
  `id_solicitud` int(11) DEFAULT NULL,
  `motivo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_reserva`
--

CREATE TABLE `historial_reserva` (
  `id_historial` int(11) NOT NULL,
  `id_solicitud_origen` int(11) NOT NULL,
  `fecha_archivo` datetime NOT NULL,
  `estado` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `detalle` text NOT NULL,
  `fecha_solicitud` datetime DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `ci_cliente` varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_solicitud`
--

CREATE TABLE `historial_solicitud` (
  `id_historial` int(11) NOT NULL,
  `id_solicitud_origen` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `fecha_archivo` datetime DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `ci_cliente` varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `material`
--

CREATE TABLE `material` (
  `id_material` int(11) NOT NULL,
  `id_serie` varchar(30) DEFAULT NULL,
  `tipo_control` varchar(10) NOT NULL DEFAULT 'CANTIDAD',
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `cantidad_total` int(11) NOT NULL,
  `cantidad_disponible` int(11) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'DISPONIBLE',
  `foto_material` varchar(900) NOT NULL,
  `fecha_baja` datetime DEFAULT NULL,
  `motivo_baja` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo`
--

CREATE TABLE `prestamo` (
  `id_solicitud` int(11) NOT NULL,
  `materia` varchar(100) DEFAULT NULL,
  `horas_solicitadas` int(11) DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `fecha_devolucion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_material`
--

CREATE TABLE `prestamo_material` (
  `id_solicitud` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha_devolucion` datetime DEFAULT NULL,
  `cantidad_devuelta` int(11) DEFAULT NULL,
  `estado_devolucion` varchar(20) DEFAULT NULL,
  `observacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_pedido`
--

CREATE TABLE `prestamo_pedido` (
  `id_solicitud` int(11) NOT NULL,
  `nombre_material` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva`
--

CREATE TABLE `reserva` (
  `id_solicitud` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_espera`
--

CREATE TABLE `reserva_espera` (
  `id_espera` int(11) NOT NULL,
  `ci_cliente` varchar(8) NOT NULL,
  `nombre_material` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'ESPERANDO' CHECK (`estado` in ('ESPERANDO','ATENDIDA','CANCELADA','VENCIDA')),
  `fecha_alta` datetime NOT NULL,
  `fecha_atencion` datetime DEFAULT NULL,
  `id_solicitud_generada` int(11) DEFAULT NULL,
  `id_solicitud_origen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_material`
--

CREATE TABLE `reserva_material` (
  `id_solicitud` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_pedido`
--

CREATE TABLE `reserva_pedido` (
  `id_solicitud` int(11) NOT NULL,
  `nombre_material` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud`
--

CREATE TABLE `solicitud` (
  `id_solicitud` int(11) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'PENDIENTE',
  `fecha_solicitud` datetime NOT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `ci_cliente` varchar(8) NOT NULL,
  `ci_administrador` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

CREATE TABLE `telefono` (
  `ci_usuario` varchar(8) NOT NULL,
  `telefono` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ci` varchar(8) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rol` varchar(20) NOT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `estado_cuenta` varchar(20) NOT NULL DEFAULT 'ACTIVA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `historial_estado_material`
--
ALTER TABLE `historial_estado_material`
  ADD PRIMARY KEY (`id_cambio`),
  ADD KEY `idx_hem_material` (`id_material`,`fecha`),
  ADD KEY `ci_responsable` (`ci_responsable`),
  ADD KEY `id_solicitud` (`id_solicitud`);

--
-- Indices de la tabla `historial_reserva`
--
ALTER TABLE `historial_reserva`
  ADD PRIMARY KEY (`id_historial`),
  ADD UNIQUE KEY `uq_historial_reserva_origen` (`id_solicitud_origen`),
  ADD KEY `idx_hist_reserva_cliente` (`ci_cliente`),
  ADD KEY `idx_hist_reserva_fecha` (`fecha`);

--
-- Indices de la tabla `historial_solicitud`
--
ALTER TABLE `historial_solicitud`
  ADD PRIMARY KEY (`id_historial`),
  ADD UNIQUE KEY `uq_historial_origen` (`id_solicitud_origen`),
  ADD KEY `ci_cliente` (`ci_cliente`),
  ADD KEY `ci_administrador` (`ci_administrador`);

--
-- Indices de la tabla `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`id_material`),
  ADD UNIQUE KEY `uq_material_serie` (`id_serie`),
  ADD KEY `idx_material_nombre_estado` (`nombre`,`estado`);

--
-- Indices de la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD PRIMARY KEY (`id_solicitud`);

--
-- Indices de la tabla `prestamo_material`
--
ALTER TABLE `prestamo_material`
  ADD PRIMARY KEY (`id_solicitud`,`id_material`),
  ADD KEY `id_material` (`id_material`);

--
-- Indices de la tabla `prestamo_pedido`
--
ALTER TABLE `prestamo_pedido`
  ADD PRIMARY KEY (`id_solicitud`,`nombre_material`);

--
-- Indices de la tabla `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id_solicitud`);

--
-- Indices de la tabla `reserva_espera`
--
ALTER TABLE `reserva_espera`
  ADD PRIMARY KEY (`id_espera`),
  ADD KEY `idx_espera_estado` (`estado`,`nombre_material`),
  ADD KEY `idx_espera_cliente` (`ci_cliente`);

--
-- Indices de la tabla `reserva_material`
--
ALTER TABLE `reserva_material`
  ADD PRIMARY KEY (`id_solicitud`,`id_material`),
  ADD KEY `id_material` (`id_material`);

--
-- Indices de la tabla `reserva_pedido`
--
ALTER TABLE `reserva_pedido`
  ADD PRIMARY KEY (`id_solicitud`,`nombre_material`);

--
-- Indices de la tabla `solicitud`
--
ALTER TABLE `solicitud`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `ci_cliente` (`ci_cliente`),
  ADD KEY `ci_administrador` (`ci_administrador`),
  ADD KEY `idx_solicitud_estado` (`estado`);

--
-- Indices de la tabla `telefono`
--
ALTER TABLE `telefono`
  ADD PRIMARY KEY (`ci_usuario`,`telefono`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ci`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `historial_estado_material`
--
ALTER TABLE `historial_estado_material`
  MODIFY `id_cambio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_reserva`
--
ALTER TABLE `historial_reserva`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_solicitud`
--
ALTER TABLE `historial_solicitud`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `material`
--
ALTER TABLE `material`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reserva_espera`
--
ALTER TABLE `reserva_espera`
  MODIFY `id_espera` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitud`
--
ALTER TABLE `solicitud`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_estado_material`
--
ALTER TABLE `historial_estado_material`
  ADD CONSTRAINT `fk_hem_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`),
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
  ADD CONSTRAINT `fk_pm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`),
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
  ADD CONSTRAINT `fk_rm_material` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`),
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
  