-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2026 a las 03:26:09
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

--
-- Volcado de datos para la tabla `historial_solicitud`
--

INSERT INTO `historial_solicitud` (`id_historial`, `id_solicitud_origen`, `tipo`, `fecha_archivo`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, NULL, 'Prestamo', '2026-08-20 16:00:00', 'FINALIZADO', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, NULL, 'Reserva', '2026-08-25 12:00:00', 'FINALIZADO', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002'),
(3, 8, 'Prestamo', '2026-10-14 15:00:00', 'FINALIZADO', '2026-10-14 08:00:00', '2026-10-14 08:30:00', NULL, '20000004', '10000001'),
(4, 13, 'Prestamo', '2026-10-28 09:30:00', 'FINALIZADO', '2026-10-26 10:00:00', '2026-10-26 11:00:00', NULL, '20000004', '10000002');

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

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`id_solicitud`, `fecha`, `hora_inicio`, `hora_fin`) VALUES
(4, '2026-11-12', '14:00:00', '16:00:00'),
(5, '2026-11-10', '10:00:00', '12:00:00'),
(6, '2026-11-10', '11:00:00', '13:00:00'),
(7, '2026-11-09', '09:00:00', '10:00:00');

--
-- Volcado de datos para la tabla `reserva_material`
--

INSERT INTO `reserva_material` (`id_solicitud`, `id_material`, `cantidad`) VALUES
(4, 19, 1),
(4, 20, 1),
(5, 23, 1),
(6, 23, 1),
(7, 17, 1);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
