-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2026 a las 06:28:30
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
(1, 1, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-05 17:34:15', '10000001', 1, NULL),
(2, 2, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-05 17:34:15', '10000001', 1, NULL),
(3, 3, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-05 17:36:38', '10000001', 2, NULL),
(4, 13, 'DISPONIBLE', 'PRESTADO', 'ENTREGAR', '2026-11-05 17:36:38', '10000001', 2, NULL),
(5, 3, 'PRESTADO', 'EN_MANTENIMIENTO', 'DEVOLVER_CON_DANO', '2026-11-05 17:37:11', '10000001', 2, 'Batería fundida'),
(6, 13, 'PRESTADO', 'EN_MANTENIMIENTO', 'DEVOLVER_CON_DANO', '2026-11-05 17:37:11', '10000001', 2, 'Placa quemada'),
(7, 25, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'CAMBIO_ESTADO', '2026-11-05 17:40:36', '10000001', NULL, 'Hélice rota.'),
(8, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-05 17:56:45', '10000001', 1, ''),
(9, 2, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-05 17:56:45', '10000001', 1, ''),
(10, 1, 'DISPONIBLE', 'RESERVADO', 'RESERVAR', '2026-11-05 22:27:24', '10000001', 15, 'Reserva aprobada directamente por administrador'),
(11, 1, 'RESERVADO', 'PRESTADO', 'ENTREGAR', '2026-11-05 22:27:37', '10000001', 15, 'Inicio de uso de reserva'),
(12, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-05 22:28:30', '10000001', 15, 'Finalización de reserva'),
(13, 1, 'DISPONIBLE', 'RESERVADO', 'RESERVAR', '2026-11-05 22:41:20', '10000001', 17, 'Reserva aprobada'),
(14, 1, 'RESERVADO', 'PRESTADO', 'ENTREGAR', '2026-11-05 22:41:44', '10000001', 17, 'Inicio de uso de reserva'),
(15, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-05 23:10:15', '10000001', 17, 'Finalización de reserva'),
(16, 1, 'DISPONIBLE', 'RESERVADO', 'RESERVAR', '2026-11-05 23:14:47', '10000001', 18, 'Reserva aprobada'),
(17, 1, 'RESERVADO', 'PRESTADO', 'ENTREGAR', '2026-11-05 23:14:55', '10000001', 18, 'Inicio de uso de reserva'),
(18, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-05 23:15:01', '10000001', 18, 'Finalización de reserva'),
(19, 1, 'DISPONIBLE', 'RESERVADO', 'RESERVAR', '2026-11-05 23:18:26', '10000001', 19, 'Reserva aprobada'),
(20, 1, 'RESERVADO', 'PRESTADO', 'ENTREGAR', '2026-11-05 23:18:34', '10000001', 19, 'Inicio de uso de reserva'),
(21, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-05 23:18:39', '10000001', 19, 'Finalización de reserva'),
(22, 31, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'CAMBIO_ESTADO', '2026-11-06 00:00:06', '10000001', NULL, '5'),
(23, 31, 'EN_MANTENIMIENTO', 'DISPONIBLE', 'CAMBIO_ESTADO', '2026-11-06 00:02:16', '10000001', NULL, '15'),
(24, 31, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'CAMBIO_ESTADO', '2026-11-06 00:02:42', '10000001', NULL, '5'),
(25, 31, 'EN_MANTENIMIENTO', 'DISPONIBLE', 'CAMBIO_ESTADO', '2026-11-06 00:17:17', '10000001', NULL, 'Prueba'),
(26, 31, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'CAMBIO_ESTADO', '2026-11-06 00:17:32', '10000001', NULL, 'prueba'),
(27, 31, 'EN_MANTENIMIENTO', 'DISPONIBLE', 'CAMBIO_ESTADO', '2026-11-06 00:24:39', '10000001', NULL, 'Prueba'),
(31, 31, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'CAMBIO_ESTADO', '2026-11-06 00:36:12', '10000001', NULL, 'p'),
(32, 31, 'EN_MANTENIMIENTO', 'DISPONIBLE', 'CAMBIO_ESTADO', '2026-11-06 00:37:50', '10000001', NULL, 'p'),
(33, 31, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'CAMBIO_ESTADO', '2026-11-06 00:38:07', '10000001', NULL, 'p'),
(34, 1, 'DISPONIBLE', 'RESERVADO', 'RESERVAR', '2026-11-06 02:13:59', '10000001', 24, 'Reserva aprobada'),
(35, 1, 'RESERVADO', 'PRESTADO', 'ENTREGAR', '2026-11-06 02:14:07', '10000001', 24, 'Inicio de uso de reserva'),
(36, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-06 02:14:12', '10000001', 24, 'Finalización de reserva'),
(37, 1, 'DISPONIBLE', 'RESERVADO', 'RESERVAR', '2026-11-06 02:17:17', '10000001', 25, 'Reserva aprobada directamente por administrador'),
(38, 1, 'RESERVADO', 'PRESTADO', 'ENTREGAR', '2026-11-06 02:17:27', '10000001', 25, 'Inicio de uso de reserva'),
(39, 1, 'PRESTADO', 'DISPONIBLE', 'DEVOLVER', '2026-11-06 02:23:43', '10000001', 25, 'Finalización de reserva');

--
-- Volcado de datos para la tabla `historial_reserva`
--

INSERT INTO `historial_reserva` (`id_historial`, `id_solicitud_origen`, `fecha_archivo`, `estado`, `fecha`, `hora_inicio`, `hora_fin`, `detalle`, `fecha_solicitud`, `fecha_validacion`, `ci_cliente`, `ci_administrador`) VALUES
(1, 8, '2026-11-05 18:10:41', 'FINALIZADO', '2026-11-05', '18:10:00', '18:14:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-05 18:10:00', '2026-11-05 18:10:22', '51191208', '10000001'),
(2, 9, '2026-11-05 18:24:12', 'FINALIZADO', '2026-11-05', '18:12:00', '18:15:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-05 18:11:40', '2026-11-05 18:11:50', '51191208', '10000001'),
(3, 14, '2026-11-05 20:46:11', 'FINALIZADO', '2026-11-05', '20:46:00', '20:48:00', 'Ceibalita ×1', '2026-11-05 20:45:15', NULL, '51191195', '10000001'),
(4, 15, '2026-11-05 22:28:30', 'FINALIZADO', '2026-11-05', '22:28:00', '22:30:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-05 22:27:24', NULL, '51191195', '10000001'),
(5, 17, '2026-11-05 23:10:15', 'FINALIZADO', '2026-11-05', '22:42:00', '22:46:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-05 22:41:08', '2026-11-05 22:41:20', '51191208', '10000001'),
(6, 13, '2026-11-05 23:10:17', 'FINALIZADO', '2026-11-05', '20:40:00', '20:42:00', 'Ceibalita ×1', '2026-11-05 20:39:23', NULL, '51191195', '10000001'),
(7, 18, '2026-11-05 23:15:01', 'FINALIZADO', '2026-11-05', '23:15:00', '23:18:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-05 23:14:32', '2026-11-05 23:14:47', '51191208', '10000001'),
(8, 19, '2026-11-05 23:18:39', 'FINALIZADO', '2026-11-05', '23:19:00', '23:23:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-05 23:18:13', '2026-11-05 23:18:26', '51191208', '10000001'),
(9, 24, '2026-11-06 02:14:12', 'FINALIZADO', '2026-11-06', '02:14:00', '02:16:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-06 02:13:49', '2026-11-06 02:13:59', '51191208', '10000001'),
(10, 25, '2026-11-06 02:23:43', 'FINALIZADO', '2026-11-06', '02:18:00', '02:20:00', 'Ceibalita ×1 (CEIB-001)', '2026-11-06 02:17:17', NULL, '51191195', '10000001');

--
-- Volcado de datos para la tabla `historial_solicitud`
--

INSERT INTO `historial_solicitud` (`id_historial`, `id_solicitud_origen`, `tipo`, `fecha_archivo`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, 2, 'Prestamo', '2026-11-05 17:37:11', 'FINALIZADO', '2026-11-05 17:36:02', '2026-11-05 17:36:25', NULL, '51191195', '10000001'),
(2, 1, 'Prestamo', '2026-11-05 17:56:45', 'FINALIZADO', '2026-11-05 17:33:51', '2026-11-05 17:34:09', NULL, '51191208', '10000001'),
(3, 22, 'Prestamo', '2026-11-06 00:59:20', 'FINALIZADO', '2026-11-06 00:42:54', '2026-11-06 00:59:10', NULL, '51191208', '10000001');

--
-- Volcado de datos para la tabla `material`
--

INSERT INTO `material` (`id_material`, `id_serie`, `tipo_control`, `nombre`, `descripcion`, `categoria`, `cantidad_total`, `cantidad_disponible`, `estado`, `foto_material`, `fecha_baja`, `motivo_baja`) VALUES
(1, 'CEIB-001', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(2, 'CEIB-002', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(3, 'CEIB-003', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 0, 'EN_MANTENIMIENTO', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(4, 'CEIB-004', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(5, 'CEIB-005', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(6, 'CEIB-006', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(7, 'CEIB-007', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(8, 'CEIB-008', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(9, 'CEIB-009', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(10, 'CEIB-010', 'SERIE', 'Ceibalita', 'Notebook educativa ceibal.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdd25c414b.jpg', NULL, NULL),
(11, NULL, 'CANTIDAD', 'Cables cocodrilo', 'Cable flexible cuyos extremos tienen pinzas metálicas con mandíbulas dentadas y resorte, diseñadas para hacer conexiones eléctricas temporales, rápidas y seguras sin necesidad de soldar o torcer cables manualmente.', 'gadgets', 50, 50, 'DISPONIBLE', 'uploads/prod_6aecdd851df64.jpg', NULL, NULL),
(12, NULL, 'CANTIDAD', 'Buzzer', 'Pequeño dispositivo electrónico que convierte la energía eléctrica en sonido. ', 'gadgets', 20, 20, 'DISPONIBLE', 'uploads/prod_6aecdde4cde8d.jpg', NULL, NULL),
(13, 'MICRO-001', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 0, 'EN_MANTENIMIENTO', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(14, 'MICRO-002', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(15, 'MICRO-003', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(16, 'MICRO-004', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(17, 'MICRO-005', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(18, 'MICRO-006', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(19, 'MICRO-007', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(20, 'MICRO-008', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(21, 'MICRO-009', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(22, 'MICRO-010', 'SERIE', 'Microbit', 'Pequeña placa programable de código abierto (4 x 5 cm) diseñada con fines educativos para enseñar programación, electrónica y robótica de manera sencilla y práctica.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aecdece83356.jpg', NULL, NULL),
(23, NULL, 'CANTIDAD', 'Robots Lego Spike', 'LEGO Education SPIKE es un sistema de aprendizaje de robótica y STEAM (ciencia, tecnología, ingeniería, arte y matemáticas) diseñado para estudiantes y aulas.', 'Robótica y Programación', 10, 10, 'DISPONIBLE', 'uploads/prod_6aece08d20141.jpg', NULL, NULL),
(24, 'D-DJIT-001', 'SERIE', 'Dron DJI Tello', 'Mini dron de iniciación y educativo, ideal para principiantes y espacios interiores.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece21e76c28.jpg', NULL, NULL),
(25, 'D-DJIT-002', 'SERIE', 'Dron DJI Tello', 'Mini dron de iniciación y educativo, ideal para principiantes y espacios interiores.', 'Robótica y Programación', 1, 0, 'EN_MANTENIMIENTO', 'uploads/prod_6aece21e76c28.jpg', NULL, NULL),
(26, 'PMKY-001', 'SERIE', 'Placa Makey Makey', 'Dispositivo electrónico educativo que permite convertir objetos cotidianos (como frutas, plastilina, papel de aluminio o líquidos) en teclas o botones para interactuar con una computadora.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece32fe6dee.jpg', NULL, NULL),
(27, 'PMKY-002', 'SERIE', 'Placa Makey Makey', 'Dispositivo electrónico educativo que permite convertir objetos cotidianos (como frutas, plastilina, papel de aluminio o líquidos) en teclas o botones para interactuar con una computadora.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece32fe6dee.jpg', NULL, NULL),
(28, 'PMKY-003', 'SERIE', 'Placa Makey Makey', 'Dispositivo electrónico educativo que permite convertir objetos cotidianos (como frutas, plastilina, papel de aluminio o líquidos) en teclas o botones para interactuar con una computadora.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece32fe6dee.jpg', NULL, NULL),
(29, 'PMKY-004', 'SERIE', 'Placa Makey Makey', 'Dispositivo electrónico educativo que permite convertir objetos cotidianos (como frutas, plastilina, papel de aluminio o líquidos) en teclas o botones para interactuar con una computadora.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece32fe6dee.jpg', NULL, NULL),
(30, 'PMKY-005', 'SERIE', 'Placa Makey Makey', 'Dispositivo electrónico educativo que permite convertir objetos cotidianos (como frutas, plastilina, papel de aluminio o líquidos) en teclas o botones para interactuar con una computadora.', 'Robótica y Programación', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece32fe6dee.jpg', NULL, NULL),
(31, NULL, 'CANTIDAD', 'Sensor fisioquímico', 'Dispositivos que permiten registrar y obtener información de variables fisicoquímicas del ambiente, que luego se visualizan en un software de análisis de datos para generar prácticas que evidencien contenidos teóricos.', 'gadgets', 10, 9, 'DISPONIBLE', 'uploads/prod_6aece40f851c9.jpg', NULL, NULL),
(32, NULL, 'CANTIDAD', 'Impresora 3D', 'Permite generar elementos tangibles a partir de un modelo 3D digital, lo que posibilita introducir y trabajar con conceptos vinculados al manejo de geometría, planos, volúmenes, primitivas y operaciones matemáticas, entre otros conocimientos.', 'Equipos', 1, 1, 'DISPONIBLE', 'uploads/prod_6aece47cb9b89.jpg', NULL, NULL);

--
-- Volcado de datos para la tabla `prestamo`
--

INSERT INTO `prestamo` (`id_solicitud`, `materia`, `horas_solicitadas`, `fecha_entrega`, `fecha_devolucion`) VALUES
(1, 'Informatica', 2, '2026-11-05 17:34:15', '2026-11-05 17:56:45'),
(2, 'Informatica', 2, '2026-11-05 17:36:38', '2026-11-05 17:37:11'),
(3, 'Informatica', 2, NULL, NULL),
(4, 'Informatica', 2, NULL, NULL),
(21, 'Biologia', 2, NULL, NULL),
(22, 'Biologia', 2, '2026-11-06 00:59:13', '2026-11-06 00:59:20'),
(23, 'Informatica', 2, NULL, NULL),
(26, 'Biologia', 2, NULL, NULL);

--
-- Volcado de datos para la tabla `prestamo_material`
--

INSERT INTO `prestamo_material` (`id_solicitud`, `id_material`, `cantidad`, `fecha_devolucion`, `cantidad_devuelta`, `estado_devolucion`, `observacion`) VALUES
(1, 1, 1, '2026-11-05 17:56:45', 1, 'DISPONIBLE', NULL),
(1, 2, 1, '2026-11-05 17:56:45', 1, 'DISPONIBLE', NULL),
(2, 3, 1, '2026-11-05 17:37:11', 1, 'EN_MANTENIMIENTO', 'Batería fundida'),
(2, 11, 1, '2026-11-05 17:37:11', 1, 'DISPONIBLE', NULL),
(2, 12, 1, '2026-11-05 17:37:11', 1, 'DISPONIBLE', NULL),
(2, 13, 1, '2026-11-05 17:37:11', 1, 'EN_MANTENIMIENTO', 'Placa quemada'),
(22, 31, 1, '2026-11-06 00:59:20', 1, 'DISPONIBLE', NULL);

--
-- Volcado de datos para la tabla `prestamo_pedido`
--

INSERT INTO `prestamo_pedido` (`id_solicitud`, `nombre_material`, `cantidad`) VALUES
(1, 'Ceibalita', 2),
(2, 'Buzzer', 1),
(2, 'Cables cocodrilo', 1),
(2, 'Ceibalita', 1),
(2, 'Microbit', 1),
(3, 'Buzzer', 2),
(4, 'Buzzer', 1),
(21, 'Ceibalita', 1),
(22, 'Sensor fisioquímico', 1),
(23, 'Ceibalita', 1),
(26, 'Ceibalita', 1);

--
-- Volcado de datos para la tabla `reserva`
--

INSERT INTO `reserva` (`id_solicitud`, `fecha`, `hora_inicio`, `hora_fin`) VALUES
(5, '2026-11-06', '17:43:00', '18:50:00'),
(6, '2026-11-06', '17:43:00', '18:50:00'),
(7, '2026-11-06', '18:05:00', '18:07:00'),
(8, '2026-11-05', '18:10:00', '18:14:00'),
(9, '2026-11-05', '18:12:00', '18:15:00'),
(10, '2026-11-05', '20:12:00', '20:13:00'),
(11, '2026-11-05', '20:17:00', '20:19:00'),
(12, '2026-11-05', '20:18:00', '20:20:00'),
(13, '2026-11-05', '20:40:00', '20:42:00'),
(14, '2026-11-05', '20:46:00', '20:48:00'),
(15, '2026-11-05', '22:28:00', '22:30:00'),
(16, '2026-11-05', '22:31:00', '22:35:00'),
(17, '2026-11-05', '22:42:00', '22:46:00'),
(18, '2026-11-05', '23:15:00', '23:18:00'),
(19, '2026-11-05', '23:19:00', '23:23:00'),
(20, '2026-11-05', '23:19:00', '23:23:00'),
(24, '2026-11-06', '02:14:00', '02:16:00'),
(25, '2026-11-06', '02:18:00', '02:20:00');

--
-- Volcado de datos para la tabla `reserva_material`
--

INSERT INTO `reserva_material` (`id_solicitud`, `id_material`, `cantidad`) VALUES
(5, 4, 1),
(6, 5, 1),
(7, 1, 1),
(7, 2, 1),
(8, 1, 1),
(9, 1, 1),
(15, 1, 1),
(17, 1, 1),
(18, 1, 1),
(19, 1, 1),
(24, 1, 1),
(25, 1, 1);

--
-- Volcado de datos para la tabla `reserva_pedido`
--

INSERT INTO `reserva_pedido` (`id_solicitud`, `nombre_material`, `cantidad`) VALUES
(5, 'Ceibalita', 1),
(6, 'Ceibalita', 1),
(7, 'Ceibalita', 2),
(8, 'Ceibalita', 1),
(9, 'Ceibalita', 1),
(10, 'Ceibalita', 1),
(11, 'Ceibalita', 1),
(12, 'Ceibalita', 1),
(13, 'Ceibalita', 1),
(14, 'Ceibalita', 1),
(15, 'Ceibalita', 1),
(16, 'Ceibalita', 1),
(17, 'Ceibalita', 1),
(18, 'Ceibalita', 1),
(19, 'Ceibalita', 1),
(20, 'Ceibalita', 1),
(24, 'Ceibalita', 1),
(25, 'Ceibalita', 1);

--
-- Volcado de datos para la tabla `solicitud`
--

INSERT INTO `solicitud` (`id_solicitud`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, 'FINALIZADO', '2026-11-05 17:33:51', '2026-11-05 17:34:09', NULL, '51191208', '10000001'),
(2, 'FINALIZADO', '2026-11-05 17:36:02', '2026-11-05 17:36:25', NULL, '51191195', '10000001'),
(3, 'RECHAZADO', '2026-11-05 17:37:42', '2026-11-05 17:38:19', 'Poco cuidado de préstamos anteriores', '51191195', '10000001'),
(4, 'CANCELADO', '2026-11-05 17:38:41', NULL, NULL, '51191195', NULL),
(5, 'CANCELADO', '2026-11-05 17:44:02', '2026-11-05 17:44:34', NULL, '51191195', '10000001'),
(6, 'CANCELADO', '2026-11-05 17:45:15', '2026-11-05 17:45:45', NULL, '51191195', '10000001'),
(7, 'CANCELADO', '2026-11-05 18:06:19', '2026-11-05 18:06:19', NULL, '51191208', '10000001'),
(8, 'FINALIZADO', '2026-11-05 18:10:00', '2026-11-05 18:10:22', NULL, '51191208', '10000001'),
(9, 'FINALIZADO', '2026-11-05 18:11:40', '2026-11-05 18:11:50', NULL, '51191208', '10000001'),
(10, 'CANCELADO', '2026-11-05 20:11:56', NULL, NULL, '51191195', NULL),
(11, 'CANCELADO', '2026-11-05 20:16:41', NULL, NULL, '51191195', NULL),
(12, 'CANCELADO', '2026-11-05 20:17:20', NULL, NULL, '51191208', NULL),
(13, 'FINALIZADO', '2026-11-05 20:39:23', NULL, NULL, '51191195', NULL),
(14, 'FINALIZADO', '2026-11-05 20:45:15', NULL, NULL, '51191195', NULL),
(15, 'FINALIZADO', '2026-11-05 22:27:24', NULL, NULL, '51191195', NULL),
(16, 'CANCELADO', '2026-11-05 22:30:56', NULL, NULL, '51191208', NULL),
(17, 'FINALIZADO', '2026-11-05 22:41:08', '2026-11-05 22:41:20', NULL, '51191208', '10000001'),
(18, 'FINALIZADO', '2026-11-05 23:14:32', '2026-11-05 23:14:47', NULL, '51191208', '10000001'),
(19, 'FINALIZADO', '2026-11-05 23:18:13', '2026-11-05 23:18:26', NULL, '51191208', '10000001'),
(20, 'RECHAZADO', '2026-11-05 23:18:55', '2026-11-05 23:19:14', 'Yo quise', '51191208', '10000001'),
(21, 'RECHAZADO', '2026-11-05 23:20:58', '2026-11-05 23:21:14', 'Prueba', '51191208', '10000001'),
(22, 'FINALIZADO', '2026-11-06 00:42:54', '2026-11-06 00:59:10', NULL, '51191208', '10000001'),
(23, 'RECHAZADO', '2026-11-06 00:59:47', '2026-11-06 01:00:58', 'Pintó', '51191208', '10000001'),
(24, 'FINALIZADO', '2026-11-06 02:13:49', '2026-11-06 02:13:59', NULL, '51191208', '10000001'),
(25, 'FINALIZADO', '2026-11-06 02:17:17', NULL, NULL, '51191195', NULL),
(26, 'CANCELADO', '2026-11-06 02:23:55', '2026-11-06 02:23:55', NULL, '51191208', '10000001');

--
-- Volcado de datos para la tabla `telefono`
--

INSERT INTO `telefono` (`ci_usuario`, `telefono`) VALUES
('11111111', '094111111'),
('51191195', '091098941'),
('51191208', '094190841'),
('55176319', '093890845'),
('56328492', '098760809');

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ci`, `nombre`, `apellido`, `contrasenia`, `email`, `rol`, `especialidad`, `estado_cuenta`) VALUES
('10000001', 'Lucía', 'Fernández', '$2y$10$7fxnQ269MQlGq/qqg4MTOeTunygb75owxXHYr8crs7Qvx4vZG1CdS', 'lucia.fernandez@cerpdeleste.edu.uy', 'ADMINISTRADOR', NULL, 'ACTIVA'),
('11111111', 'Administrador', 'Ceilab', '$2y$10$7k4lsbqX.Cg4jNO9ouLHv.fwW7ANtTLcp3FmIUTirPa3.LWh3zeQy', 'admn@ceilab.com', 'ADMINISTRADOR', NULL, 'ACTIVA'),
('51191195', 'Carlos', 'Rodríguez', '$2y$10$qu9wa3osHlqs98cU5CN6ueM3Zek6BTmAjdHOh8Ybraa/qn88Zwbdm', 'tiki@example.com', 'CLIENTE', 'Informatica', 'ACTIVA'),
('51191208', 'José', 'Rodríguez', '$2y$10$RkbbosLQRKDvbnxA7stNoOd28ENLB334NH//NJW/3IVSlBgDrokKG', 'joseR@ceilab.com', 'CLIENTE', 'Informatica', 'ACTIVA'),
('55176319', 'Luciano', 'Delgado', '$2y$10$q5JUha3CQO5RDowzkvDERu.JQYIm3JGKQHPHqc.Wt.SA4ylb3xa0O', 'lucianoD@example.com', 'CLIENTE', 'Literatura', 'ACTIVA'),
('56328492', 'Matías', 'Indart', '$2y$10$hY.7L.HAkDHWup7vRsscAeNAMuxF9e1g5BwAQQ9e0AQx8ZRtdK32O', 'matiCAPO@ceibal.com', 'CLIENTE', 'Informatica', 'ACTIVA');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
