-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- seed.sql v7 (alineado con schema.sql v7: un equipo por fila con id_serie)
-- Estados de solicitud: PENDIENTE, APROBADO, RECHAZADO, ACTIVO, FINALIZADO, CANCELADO
-- Estados de material:  DISPONIBLE, PRESTADO, EN_MANTENIMIENTO, DADO_DE_BAJA
-- Contraseña real de TODOS los usuarios de prueba: 123456
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIO
-- 2 administradores + 4 clientes
-- ------------------------------------------------------------
INSERT INTO `usuario` (`ci`, `nombre`, `apellido`, `contrasenia`, `email`, `rol`, `especialidad`) VALUES
('10000001', 'Lucía',     'Fernández', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'lucia.fernandez@cerpdeleste.edu.uy',   'ADMINISTRADOR', NULL),
('10000002', 'Martín',    'Rodríguez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'martin.rodriguez@cerpdeleste.edu.uy',  'ADMINISTRADOR', NULL),
('20000001', 'Ana',       'Pereira',   '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'ana.pereira@cerpdeleste.edu.uy',       'CLIENTE',       'Física'),
('20000002', 'Carlos',    'Gómez',     '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'carlos.gomez@cerpdeleste.edu.uy',      'CLIENTE',       'Matemática'),
('20000003', 'Valentina', 'Silva',     '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'valentina.silva@cerpdeleste.edu.uy',   'CLIENTE',       'Informática'),
('20000004', 'Diego',     'Martínez',  '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'diego.martinez@cerpdeleste.edu.uy',    'CLIENTE',       'Informática');

-- ------------------------------------------------------------
-- TELEFONO
-- ------------------------------------------------------------
INSERT INTO `telefono` (`ci_usuario`, `telefono`) VALUES
('10000001', '099111111'),
('20000001', '099222222'),
('20000001', '099222233'),
('20000002', '099333333');

-- ------------------------------------------------------------
-- MATERIAL
-- Equipos con número de serie (una fila por unidad):
--   Ceibalita 1-10 (CEIB-0001 prestada en la solicitud 2,
--                   CEIB-0010 en mantenimiento)
--   Micro:bit 11-16 (MB-0006 dada de baja)
--   Dron 17-18 (DRON-02 en mantenimiento)
--   LEGO SPIKE 19-22, Impresora 3D 23
-- Consumibles por cantidad: Cable pinza cocodrilo 24, Buzzer 25
-- ------------------------------------------------------------
INSERT INTO `material` (`id_material`, `id_serie`, `tipo_control`, `nombre`, `descripcion`, `categoria`, `cantidad_total`, `cantidad_disponible`, `estado`, `foto_material`, `fecha_baja`, `motivo_baja`) VALUES
(1 , 'CEIB-0001', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 0, 'PRESTADO', '/uploads/ceibalita.jpg', NULL, NULL),
(2 , 'CEIB-0002', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(3 , 'CEIB-0003', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(4 , 'CEIB-0004', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(5 , 'CEIB-0005', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(6 , 'CEIB-0006', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(7 , 'CEIB-0007', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(8 , 'CEIB-0008', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(9 , 'CEIB-0009', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 1, 'DISPONIBLE', '/uploads/ceibalita.jpg', NULL, NULL),
(10, 'CEIB-0010', 'SERIE', 'Ceibalita', 'Notebook educativa Ceibal', 'Equipos', 1, 0, 'EN_MANTENIMIENTO', '/uploads/ceibalita.jpg', NULL, NULL),
(11, 'MB-0001', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/microbit.jpg', NULL, NULL),
(12, 'MB-0002', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/microbit.jpg', NULL, NULL),
(13, 'MB-0003', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/microbit.jpg', NULL, NULL),
(14, 'MB-0004', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/microbit.jpg', NULL, NULL),
(15, 'MB-0005', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/microbit.jpg', NULL, NULL),
(16, 'MB-0006', 'SERIE', 'Micro:bit', 'Placa programable micro:bit', 'Robótica y Programación', 1, 0, 'DADO_DE_BAJA', '/uploads/microbit.jpg', '2026-09-30 10:00:00', 'Placa quemada, sin reparación'),
(17, 'DRON-01', 'SERIE', 'Dron', 'Dron para programación de vuelo', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/dron.jpg', NULL, NULL),
(18, 'DRON-02', 'SERIE', 'Dron', 'Dron para programación de vuelo', 'Robótica y Programación', 1, 0, 'EN_MANTENIMIENTO', '/uploads/dron.jpg', NULL, NULL),
(19, 'SPIKE-01', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/lego_spike.jpg', NULL, NULL),
(20, 'SPIKE-02', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/lego_spike.jpg', NULL, NULL),
(21, 'SPIKE-03', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/lego_spike.jpg', NULL, NULL),
(22, 'SPIKE-04', 'SERIE', 'LEGO SPIKE', 'Kit de robótica LEGO SPIKE', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/lego_spike.jpg', NULL, NULL),
(23, 'IMP3D-01', 'SERIE', 'Impresora 3D', 'Impresora 3D del laboratorio', 'Robótica y Programación', 1, 1, 'DISPONIBLE', '/uploads/impresora3d.jpg', NULL, NULL),
(24, NULL, 'CANTIDAD', 'Cable pinza cocodrilo', 'Cable con pinzas cocodrilo', 'Gadgets', 20, 20, 'DISPONIBLE', '/uploads/cocodrilo.jpg', NULL, NULL),
(25, NULL, 'CANTIDAD', 'Buzzer', 'Buzzer para prácticas con micro:bit', 'Gadgets', 15, 15, 'DISPONIBLE', '/uploads/buzzer.jpg', NULL, NULL);

-- ------------------------------------------------------------
-- SOLICITUD
-- Préstamos: 1 y 10 PENDIENTE, 9 APROBADO, 2 ACTIVO, 8 FINALIZADO, 3 RECHAZADO
-- Reservas:  4 y 6 PENDIENTE, 5 APROBADO, 7 RECHAZADO
-- ------------------------------------------------------------
INSERT INTO `solicitud` (`id_solicitud`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, 'PENDIENTE',  '2026-09-25 09:00:00', NULL,                  NULL,                                    '20000001', NULL),
(2, 'ACTIVO',     '2026-09-08 08:30:00', '2026-09-08 09:00:00', NULL,                                    '20000002', '10000001'),
(3, 'RECHAZADO',  '2026-09-20 10:00:00', '2026-09-20 11:00:00', 'Cédula con préstamos vencidos',        '20000003', '10000002'),
(4, 'PENDIENTE',  '2026-09-26 15:00:00', NULL,                  NULL,                                    '20000004', NULL),
(5, 'APROBADO',   '2026-09-24 09:00:00', '2026-09-24 10:00:00', NULL,                                    '20000001', '10000001'),
(6, 'PENDIENTE',  '2026-09-26 16:00:00', NULL,                  NULL,                                    '20000002', NULL),
(7, 'RECHAZADO',  '2026-09-22 09:00:00', '2026-09-22 12:00:00', 'Horario en conflicto con otra reserva', '20000003', '10000002'),
(8, 'FINALIZADO', '2026-09-14 08:00:00', '2026-09-14 08:30:00', NULL,                                    '20000004', '10000001'),
(9, 'APROBADO',   '2026-10-12 09:00:00', '2026-10-12 10:00:00', NULL,                                    '20000003', '10000001'),
(10,'PENDIENTE',  '2026-10-12 11:00:00', NULL,                  NULL,                                    '20000004', NULL);

-- ------------------------------------------------------------
-- PRESTAMO
-- ------------------------------------------------------------
INSERT INTO `prestamo` (`id_solicitud`, `materia`, `horas_solicitadas`, `fecha_entrega`, `fecha_devolucion`) VALUES
(1, 'Física',       4, NULL,                  NULL),
(2, 'Matemática',   8, '2026-09-10 09:00:00', NULL),
(3, 'Informática',      2, NULL,                  NULL),
(8, 'Informática',  6, '2026-09-15 09:00:00', '2026-09-15 15:00:00'),
(9, 'Informática',  4, NULL,                  NULL),
(10,'Física',       2, NULL,                  NULL);

-- ------------------------------------------------------------
-- RESERVA
-- ------------------------------------------------------------
INSERT INTO `reserva` (`id_solicitud`, `fecha`, `hora_inicio`, `hora_fin`) VALUES
(4, '2026-10-15', '14:00:00', '16:00:00'),
(5, '2026-10-13', '10:00:00', '12:00:00'),
(6, '2026-10-13', '11:00:00', '13:00:00'),
(7, '2026-09-22', '09:00:00', '10:00:00');

-- ------------------------------------------------------------
-- PRESTAMO_PEDIDO (lo que pidió el cliente, por nombre)
-- ------------------------------------------------------------
INSERT INTO `prestamo_pedido` (`id_solicitud`, `nombre_material`, `cantidad`) VALUES
(1,  'Ceibalita',             1),
(1,  'Cable pinza cocodrilo', 2),
(2,  'Ceibalita',             1),
(3,  'Micro:bit',             1),
(8,  'Ceibalita',             1),
(9,  'Ceibalita',             2),
(9,  'Buzzer',                3),
(10, 'Dron',                  2);

-- ------------------------------------------------------------
-- PRESTAMO_MATERIAL (lo que se entregó, unidad por unidad)
-- Solo los préstamos ya entregados: 2 (activo) y 8 (devuelto)
-- ------------------------------------------------------------
INSERT INTO `prestamo_material` (`id_solicitud`, `id_material`, `cantidad`, `fecha_devolucion`, `cantidad_devuelta`, `estado_devolucion`, `observacion`) VALUES
(2, 1, 1, NULL,                  NULL, NULL,         NULL),
(8, 2, 1, '2026-09-15 15:00:00', 1,    'DISPONIBLE', 'Sin observaciones');

-- ------------------------------------------------------------
-- RESERVA_MATERIAL (apunta a unidades concretas)
-- ------------------------------------------------------------
INSERT INTO `reserva_material` (`id_solicitud`, `id_material`, `cantidad`) VALUES
(4, 19, 1),
(4, 20, 1),
(5, 23, 1),
(6, 23, 1),
(7, 17, 1);

-- ------------------------------------------------------------
-- HISTORIAL_ESTADO_MATERIAL
-- ------------------------------------------------------------
INSERT INTO `historial_estado_material` (`id_material`, `estado_anterior`, `estado_nuevo`, `evento`, `fecha`, `ci_responsable`, `id_solicitud`, `motivo`) VALUES
(2,  'DISPONIBLE', 'PRESTADO',         'ENTREGAR',              '2026-09-15 09:00:00', '10000001', 8,    NULL),
(2,  'PRESTADO',   'DISPONIBLE',       'DEVOLVER',              '2026-09-15 15:00:00', '10000001', 8,    'Sin observaciones'),
(1,  'DISPONIBLE', 'PRESTADO',         'ENTREGAR',              '2026-09-10 09:00:00', '10000001', 2,    NULL),
(18, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'ENVIAR_MANTENIMIENTO',  '2026-09-28 11:00:00', '10000002', NULL, 'Hélice rota'),
(16, 'DISPONIBLE', 'DADO_DE_BAJA',     'DAR_DE_BAJA',           '2026-09-30 10:00:00', '10000001', NULL, 'Placa quemada, sin reparación'),
(10, 'DISPONIBLE', 'EN_MANTENIMIENTO', 'ENVIAR_MANTENIMIENTO',  '2026-10-01 14:00:00', '10000002', NULL, 'Pantalla con píxeles muertos');

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- (se archiva al finalizar, por eso el estado es FINALIZADO)
-- ------------------------------------------------------------
INSERT INTO `historial_solicitud` (`id_historial`, `id_solicitud_origen`, `tipo`, `fecha_archivo`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, NULL, 'Prestamo', '2026-08-20 16:00:00', 'FINALIZADO', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, NULL, 'Reserva',  '2026-08-25 12:00:00', 'FINALIZADO', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002'),
(3, 8,    'Prestamo', '2026-09-15 15:00:00', 'FINALIZADO', '2026-09-14 08:00:00', '2026-09-14 08:30:00', NULL, '20000004', '10000001');

SET FOREIGN_KEY_CHECKS = 1;