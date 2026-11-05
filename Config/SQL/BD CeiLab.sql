-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2026 a las 03:13:14
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

--
-- Volcado de datos para la tabla `historial_estado_material`
--

INSERT INTO `historial_estado_material` (`id_cambio`, `id_material`, `estado_anterior`, `estado_nuevo`, `evento`, `fecha`, `ci_responsable`, `id_solicitud`, `motivo`) VALUES
(1, 16, 'DISPONIBLE', 'DADO_DE_BAJA', 'DAR_DE_BAJA', '2026-09-30 10:00:00', '10000001', NULL, 'Placa quemada, sin reparación'),
(2, 10, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'ENVIAR_MANTENIMIENTO', '2026-10-01 14:00:00', '10000002', NULL, 'Pantalla con píxeles muertos'),
(3, 2, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-10-14 09:00:00', '10000001', 8, NULL),
(4, 2, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-10-14 15:00:00', '10000001', 8, 'Sin observaciones'),
(5, 18, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-10-27 09:00:00', '10000002', 13, NULL),
(6, 18, 'PRESTADO', 'EN_MANTENIMIENTO', 'DEVOLVER_CON_DANO', '2026-10-28 09:30:00', '10000002', 13, 'Hélice rota al volver del préstamo'),
(7, 19, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-03 08:00:00', '10000002', 11, NULL),
(8, 20, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-03 08:00:00', '10000002', 11, NULL),
(9, 1, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-03 09:00:00', '10000001', 2, NULL);

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

--
-- Volcado de datos para la tabla `historial_solicitud`
--

INSERT INTO `historial_solicitud` (`id_historial`, `id_solicitud_origen`, `tipo`, `fecha_archivo`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, NULL, 'Prestamo', '2026-08-20 16:00:00', 'FINALIZADO', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, NULL, 'Reserva', '2026-08-25 12:00:00', 'FINALIZADO', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002'),
(3, 8, 'Prestamo', '2026-10-14 15:00:00', 'FINALIZADO', '2026-10-14 08:00:00', '2026-10-14 08:30:00', NULL, '20000004', '10000001'),
(4, 13, 'Prestamo', '2026-10-28 09:30:00', 'FINALIZADO', '2026-10-26 10:00:00', '2026-10-26 11:00:00', NULL, '20000004', '10000002');

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

--
-- Volcado de datos para la tabla `material`
--

INSERT INTO `material` (`id_material`, `id_serie`, `tipo_control`, `nombre`, `descripcion`, `categoria`, `cantidad_total`, `cantidad_disponible`, `estado`, `foto_material`, `fecha_baja`, `motivo_baja`) VALUES
(1, 'CEIB-0001', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 0, 'PRESTADO', 'uploads/default.png', NULL, NULL),
(2, 'CEIB-0002', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(3, 'CEIB-0003', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(4, 'CEIB-0004', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(5, 'CEIB-0005', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(6, 'CEIB-0006', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(7, 'CEIB-0007', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(8, 'CEIB-0008', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(9, 'CEIB-0009', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(10, 'CEIB-0010', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 0, 'EN_MANTENIMIENTO', 'uploads/default.png', NULL, NULL),
(11, 'MB-0001', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(12, 'MB-0002', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(13, 'MB-0003', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(14, 'MB-0004', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(15, 'MB-0005', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(16, 'MB-0006', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 0, 'DADO_DE_BAJA', 'uploads/default.png', '2026-09-30 10:00:00', 'Placa quemada, sin reparación'),
(17, 'DRON-01', 'SERIE', 'Dron', 'Dron para programación de vuelo', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(18, 'DRON-02', 'SERIE', 'Dron', 'Dron para programación de vuelo', 'Robótica y Programación', 1, 0, 'EN_MANTENIMIENTO', 'uploads/default.png', NULL, NULL),
(19, 'SPIKE-01', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 0, 'PRESTADO', 'uploads/default.png', NULL, NULL),
(20, 'SPIKE-02', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 0, 'PRESTADO', 'uploads/default.png', NULL, NULL),
(21, 'SPIKE-03', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(22, 'SPIKE-04', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(23, 'IMP3D-01', 'SERIE', 'Impresora 3D', 'Impresora 3D del laboratorio', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(24, NULL, 'CANTIDAD', 'Cable pinza cocodrilo', 'Cable con pinzas cocodrilo', 'Gadgets', 20, 20, 'DISPONIBLE', 'uploads/default.png', NULL, NULL),
(25, NULL, 'CANTIDAD', 'Buzzer', 'Buzzer para prácticas con micro:bit', 'Gadgets', 15, 11, 'DISPONIBLE', 'uploads/default.png', NULL, NULL);

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

--
-- Volcado de datos para la tabla `prestamo`
--

INSERT INTO `prestamo` (`id_solicitud`, `materia`, `horas_solicitadas`, `fecha_entrega`, `fecha_devolucion`) VALUES
(1, 'Física', 4, NULL, NULL),
(2, 'Matemática', 8, '2026-11-03 09:00:00', NULL),
(3, 'Informática', 2, NULL, NULL),
(8, 'Informática', 6, '2026-10-14 09:00:00', '2026-10-14 15:00:00'),
(9, 'Informática', 4, NULL, NULL),
(10, 'Física', 2, NULL, NULL),
(11, 'Robótica educativa', 24, '2026-11-03 08:00:00', NULL),
(12, 'Física', 3, NULL, NULL),
(13, 'Informática', 24, '2026-10-27 09:00:00', '2026-10-28 09:30:00');

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

--
-- Volcado de datos para la tabla `prestamo_material`
--

INSERT INTO `prestamo_material` (`id_solicitud`, `id_material`, `cantidad`, `fecha_devolucion`, `cantidad_devuelta`, `estado_devolucion`, `observacion`) VALUES
(2, 1, 1, NULL, NULL, NULL, NULL),
(8, 2, 1, '2026-10-14 15:00:00', 1, 'DISPONIBLE', 'Sin observaciones'),
(11, 19, 1, NULL, NULL, NULL, NULL),
(11, 20, 1, NULL, NULL, NULL, NULL),
(11, 25, 4, NULL, NULL, NULL, NULL),
(13, 18, 1, '2026-10-28 09:30:00', 1, 'EN_MANTENIMIENTO', 'Hélice rota al volver del préstamo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo_pedido`
--

CREATE TABLE `prestamo_pedido` (
  `id_solicitud` int(11) NOT NULL,
  `nombre_material` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prestamo_pedido`
--

INSERT INTO `prestamo_pedido` (`id_solicitud`, `nombre_material`, `cantidad`) VALUES
(1, 'Cable pinza cocodrilo', 2),
(1, 'Ceibalita', 1),
(2, 'Ceibalita', 1),
(3, 'Micro:bit', 1),
(8, 'Ceibalita', 1),
(9, 'Buzzer', 3),
(9, 'Ceibalita', 2),
(10, 'Dron', 2),
(11, 'Buzzer', 4),
(11, 'LEGO SPIKE', 2),
(12, 'Impresora 3D', 1),
(13, 'Dron', 1);

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

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`id_solicitud`, `fecha`, `hora_inicio`, `hora_fin`) VALUES
(4, '2026-11-12', '14:00:00', '16:00:00'),
(5, '2026-11-10', '10:00:00', '12:00:00'),
(6, '2026-11-10', '11:00:00', '13:00:00'),
(7, '2026-11-09', '09:00:00', '10:00:00');

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

--
-- Volcado de datos para la tabla `reserva_material`
--

INSERT INTO `reserva_material` (`id_solicitud`, `id_material`, `cantidad`) VALUES
(4, 19, 1),
(4, 20, 1),
(5, 23, 1),
(6, 23, 1),
(7, 17, 1);

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

--
-- Volcado de datos para la tabla `solicitud`
--

INSERT INTO `solicitud` (`id_solicitud`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, 'APROBADO', '2026-11-03 14:00:00', '2026-11-04 23:12:33', NULL, '20000001', '10000001'),
(2, 'ACTIVO', '2026-11-02 08:30:00', '2026-11-02 09:00:00', NULL, '20000002', '10000001'),
(3, 'RECHAZADO', '2026-10-20 10:00:00', '2026-10-20 11:00:00', 'Cédula con préstamos vencidos', '20000003', '10000002'),
(4, 'PENDIENTE', '2026-11-03 15:00:00', NULL, NULL, '20000004', NULL),
(5, 'APROBADO', '2026-11-02 09:00:00', '2026-11-02 10:00:00', NULL, '20000001', '10000001'),
(6, 'PENDIENTE', '2026-11-03 16:00:00', NULL, NULL, '20000002', NULL),
(7, 'RECHAZADO', '2026-10-22 09:00:00', '2026-10-22 12:00:00', 'Horario en conflicto con otra reserva', '20000003', '10000002'),
(8, 'FINALIZADO', '2026-10-14 08:00:00', '2026-10-14 08:30:00', NULL, '20000004', '10000001'),
(9, 'APROBADO', '2026-11-03 09:00:00', '2026-11-03 10:00:00', NULL, '20000003', '10000001'),
(10, 'PENDIENTE', '2026-11-04 09:00:00', NULL, NULL, '20000004', NULL),
(11, 'ACTIVO', '2026-11-02 15:00:00', '2026-11-02 16:00:00', NULL, '20000005', '10000002'),
(12, 'CANCELADO', '2026-10-27 11:00:00', NULL, NULL, '20000001', NULL),
(13, 'FINALIZADO', '2026-10-26 10:00:00', '2026-10-26 11:00:00', NULL, '20000004', '10000002');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

CREATE TABLE `telefono` (
  `ci_usuario` varchar(8) NOT NULL,
  `telefono` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `telefono`
--

INSERT INTO `telefono` (`ci_usuario`, `telefono`) VALUES
('10000001', '099111111'),
('10000002', '099111122'),
('20000001', '099222222'),
('20000001', '099222233'),
('20000002', '099333333'),
('20000003', '099444444'),
('20000004', '099555555'),
('20000005', '099666666'),
('20000006', '099777777');

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
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ci`, `nombre`, `apellido`, `contrasenia`, `email`, `rol`, `especialidad`, `estado_cuenta`) VALUES
('10000001', 'Lucía', 'Fernández', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'lucia.fernandez@cerpdeleste.edu.uy', 'ADMINISTRADOR', NULL, 'ACTIVA'),
('10000002', 'Martín', 'Rodríguez', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'martin.rodriguez@cerpdeleste.edu.uy', 'ADMINISTRADOR', NULL, 'ACTIVA'),
('20000001', 'Ana', 'Pereira', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'ana.pereira@cerpdeleste.edu.uy', 'CLIENTE', 'Física', 'ACTIVA'),
('20000002', 'Carlos', 'Gómez', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'carlos.gomez@cerpdeleste.edu.uy', 'CLIENTE', 'Matemática', 'ACTIVA'),
('20000003', 'Valentina', 'Silva', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'valentina.silva@cerpdeleste.edu.uy', 'CLIENTE', 'Informática', 'ACTIVA'),
('20000004', 'Diego', 'Martínez', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'diego.martinez@cerpdeleste.edu.uy', 'CLIENTE', 'Informática', 'ACTIVA'),
('20000005', 'Sofía', 'Techera', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'sofia.techera@cerpdeleste.edu.uy', 'CLIENTE', 'Química', 'ACTIVA'),
('20000006', 'Joaquín', 'Barrios', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'joaquin.barrios@cerpdeleste.edu.uy', 'CLIENTE', 'Historia', 'INACTIVA');

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
  MODIFY `id_cambio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `historial_reserva`
--
ALTER TABLE `historial_reserva`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_solicitud`
--
ALTER TABLE `historial_solicitud`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `material`
--
ALTER TABLE `material`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `reserva_espera`
--
ALTER TABLE `reserva_espera`
  MODIFY `id_espera` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitud`
--
ALTER TABLE `solicitud`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
