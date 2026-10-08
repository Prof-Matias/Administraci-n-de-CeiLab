-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- seed.sql v6 (Estados alineados con schema.sql v6; stock y fechas coherentes)
-- Estados válidos: PENDIENTE, APROBADO, RECHAZADO, ACTIVO, FINALIZADO
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
('20000001', 'Ana',       'Pereira',   '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'ana.pereira@cerpdeleste.edu.uy',       'CLIENTE',       NULL),
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
-- ------------------------------------------------------------
INSERT INTO `material` (`id_material`, `nombre`, `descripcion`, `categoria`, `cantidad_total`, `cantidad_disponible`, `estado`, `foto_material`) VALUES
(1, 'Ceibalita',              'Notebook educativa Ceibal',              'Equipos',                 10, 9,  'Disponible', '/uploads/ceibalita.jpg'),
(2, 'Micro:bit',              'Placa programable micro:bit',            'Robótica y Programación', 6,  6,  'Disponible', '/uploads/microbit.jpg'),
(3, 'Dron',                   'Dron para programación de vuelo',        'Robótica y Programación', 2,  2,  'Disponible', '/uploads/dron.jpg'),
(4, 'LEGO SPIKE',             'Kit de robótica LEGO SPIKE',             'Robótica y Programación', 4,  4,  'Disponible', '/uploads/lego_spike.jpg'),
(5, 'Impresora 3D',           'Impresora 3D del laboratorio',           'Robótica y Programación', 1,  1,  'Disponible', '/uploads/impresora3d.jpg'),
(6, 'Cable pinza cocodrilo',  'Cable con pinzas cocodrilo',             'Gadgets',                 20, 20, 'Disponible', '/uploads/cocodrilo.jpg'),
(7, 'Buzzer',                 'Buzzer para prácticas con micro:bit',    'Gadgets',                 15, 15, 'Disponible', '/uploads/buzzer.jpg');

-- ------------------------------------------------------------
-- SOLICITUD
-- 3 PENDIENTE (1, 4, 6), 1 APROBADO (5), 1 ACTIVO (2),
-- 1 FINALIZADO (8), 2 RECHAZADO (3, 7)
-- ------------------------------------------------------------
INSERT INTO `solicitud` (`id_solicitud`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, 'PENDIENTE',  '2026-09-25 09:00:00', NULL,                  NULL,                                    '20000001', NULL),
(2, 'ACTIVO',     '2026-09-08 08:30:00', '2026-09-08 09:00:00', NULL,                                    '20000002', '10000001'),
(3, 'RECHAZADO',  '2026-09-20 10:00:00', '2026-09-20 11:00:00', 'Cédula con préstamos vencidos',        '20000003', '10000002'),
(4, 'PENDIENTE',  '2026-09-26 15:00:00', NULL,                  NULL,                                    '20000004', NULL),
(5, 'APROBADO',   '2026-09-24 09:00:00', '2026-09-24 10:00:00', NULL,                                    '20000001', '10000001'),
(6, 'PENDIENTE',  '2026-09-26 16:00:00', NULL,                  NULL,                                    '20000002', NULL),
(7, 'RECHAZADO',  '2026-09-22 09:00:00', '2026-09-22 12:00:00', 'Horario en conflicto con otra reserva', '20000003', '10000002'),
(8, 'FINALIZADO', '2026-09-14 08:00:00', '2026-09-14 08:30:00', NULL,                                    '20000004', '10000001');

-- ------------------------------------------------------------
-- PRESTAMO
-- ------------------------------------------------------------
INSERT INTO `prestamo` (`id_solicitud`, `materia`, `horas_solicitadas`, `fecha_entrega`, `fecha_devolucion`) VALUES
(1, 'Física',       4, NULL,                  NULL),
(2, 'Matemática',   8, '2026-09-10 09:00:00', NULL),
(3, 'Química',      2, NULL,                  NULL),
(8, 'Informática',  6, '2026-09-15 09:00:00', '2026-09-15 15:00:00');

-- ------------------------------------------------------------
-- RESERVA
-- ------------------------------------------------------------
INSERT INTO `reserva` (`id_solicitud`, `fecha`, `hora_inicio`, `hora_fin`) VALUES
(4, '2026-10-15', '14:00:00', '16:00:00'),
(5, '2026-10-13', '10:00:00', '12:00:00'),
(6, '2026-10-13', '11:00:00', '13:00:00'),
(7, '2026-09-22', '09:00:00', '10:00:00');

-- ------------------------------------------------------------
-- PRESTAMO_MATERIAL
-- ------------------------------------------------------------
INSERT INTO `prestamo_material` (`id_solicitud`, `id_material`, `cantidad`) VALUES
(1, 1, 1),
(1, 6, 2),
(2, 1, 1),
(3, 2, 1),
(8, 1, 1);

-- ------------------------------------------------------------
-- RESERVA_MATERIAL
-- ------------------------------------------------------------
INSERT INTO `reserva_material` (`id_solicitud`, `id_material`, `cantidad`) VALUES
(4, 4, 2),
(5, 5, 1),
(6, 5, 1),
(7, 3, 1);

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- (se archiva al finalizar, por eso el estado es FINALIZADO)
-- ------------------------------------------------------------
INSERT INTO `historial_solicitud` (`id_historial`, `tipo`, `fecha_archivo`, `estado`, `fecha_solicitud`, `fecha_validacion`, `motivo_rechazo`, `ci_cliente`, `ci_administrador`) VALUES
(1, 'Prestamo', '2026-08-20 16:00:00', 'FINALIZADO', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, 'Reserva',  '2026-08-25 12:00:00', 'FINALIZADO', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002');

SET FOREIGN_KEY_CHECKS = 1;