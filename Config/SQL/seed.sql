-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- seed.sql v2 — Datos de prueba (esquema con Usuario fusionado)
-- Contraseña real de TODOS los usuarios de prueba: 123456
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIO
-- 2 administradores (DOT) + 4 clientes
-- Especialidad solo cargada en algunos clientes (a propósito,
-- para simular el caso en que el usuario no la completó)
-- ------------------------------------------------------------
INSERT INTO `usuario` (`CI`, `Nombre`, `Apellido`, `Contraseña`, `Email`, `Rol`, `Especialidad`) VALUES
('10000001', 'Lucía',     'Fernández', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'lucia.fernandez@cerpdeleste.edu.uy',   'ADMINISTRADOR', NULL),
('10000002', 'Martín',    'Rodríguez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'martin.rodriguez@cerpdeleste.edu.uy',  'ADMINISTRADOR', NULL),
('20000001', 'Ana',       'Pereira',   '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'ana.pereira@cerpdeleste.edu.uy',       'CLIENTE',       NULL),
('20000002', 'Carlos',    'Gómez',     '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'carlos.gomez@cerpdeleste.edu.uy',      'CLIENTE',       'Matemática'),
('20000003', 'Valentina', 'Silva',     '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'valentina.silva@cerpdeleste.edu.uy',   'CLIENTE',       'Informática'),
('20000004', 'Diego',     'Martínez',  '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'diego.martinez@cerpdeleste.edu.uy',    'CLIENTE',       'Informática');

-- ------------------------------------------------------------
-- TELEFONO (multivaluado: Ana tiene 2 teléfonos cargados)
-- ------------------------------------------------------------
INSERT INTO `telefono` (`CI_Usuario`, `Telefono`) VALUES
('10000001', '099111111'),
('20000001', '099222222'),
('20000001', '099222233'),
('20000002', '099333333');

-- ------------------------------------------------------------
-- MATERIAL
-- ------------------------------------------------------------
INSERT INTO `material` (`ID_Material`, `Nombre`, `Descripcion`, `Categoria`, `Cantidad_Total`, `Cantidad_Disponible`, `Estado`) VALUES
(1, 'Ceibalita',              'Notebook educativa Ceibal',               'Equipos',                   10, 7, 'Disponible'),
(2, 'Micro:bit',              'Placa programable micro:bit',             'Robótica y Programación',   6,  4, 'Disponible'),
(3, 'Dron',                   'Dron para programación de vuelo',         'Robótica y Programación',   2,  0, 'Reservado'),
(4, 'LEGO SPIKE',             'Kit de robótica LEGO SPIKE',              'Robótica y Programación',   4,  4, 'Disponible'),
(5, 'Impresora 3D',           'Impresora 3D del laboratorio',            'Robótica y Programación',   1,  1, 'Disponible'),
(6, 'Cable pinza cocodrilo',  'Cable con pinzas cocodrilo',              'Gadgets',                   20, 18, 'Disponible'),
(7, 'Buzzer',                 'Buzzer para prácticas con micro:bit',     'Gadgets',                   15, 15, 'Disponible');

-- ------------------------------------------------------------
-- SOLICITUD
-- ------------------------------------------------------------
INSERT INTO `solicitud` (`ID_Solicitud`, `Estado`, `Fecha_Solicitud`, `Fecha_Validacion`, `Motivo_Rechazo`, `CI_Cliente`, `CI_Administrador`) VALUES
(1, 'Pendiente', '2026-09-25 09:00:00', NULL,                  NULL,                                  '20000001', NULL),
(2, 'Aprobado',  '2026-09-08 08:30:00', '2026-09-08 09:00:00', NULL,                                  '20000002', '10000001'),
(3, 'Rechazado', '2026-09-20 10:00:00', '2026-09-20 11:00:00', 'Cédula con préstamos vencidos',       '20000003', '10000002'),
(4, 'Pendiente', '2026-09-26 15:00:00', NULL,                  NULL,                                  '20000004', NULL),
(5, 'Aprobado',  '2026-09-24 09:00:00', '2026-09-24 10:00:00', NULL,                                  '20000001', '10000001'),
(6, 'Pendiente', '2026-09-26 16:00:00', NULL,                  NULL,                                  '20000002', NULL),
(7, 'Rechazado', '2026-09-22 09:00:00', '2026-09-22 12:00:00', 'Horario en conflicto con otra reserva','20000003', '10000002'),
(8, 'Aprobado',  '2026-09-14 08:00:00', '2026-09-14 08:30:00', NULL,                                  '20000004', '10000001');

-- ------------------------------------------------------------
-- PRESTAMO
-- ------------------------------------------------------------
INSERT INTO `prestamo` (`ID_Solicitud`, `Materia`, `Horas_Solicitadas`, `Fecha_Entrega`, `Fecha_Devolucion`) VALUES
(1, 'Física',       4, NULL,                  NULL),
(2, 'Matemática',   8, '2026-09-10 09:00:00', NULL),
(3, 'Química',      2, NULL,                  NULL),
(8, 'Informática',  6, '2026-09-15 09:00:00', '2026-09-15 15:00:00');

-- ------------------------------------------------------------
-- RESERVA
-- ------------------------------------------------------------
INSERT INTO `reserva` (`ID_Solicitud`, `Fecha`, `Hora_Inicio`, `Hora_Fin`) VALUES
(4, '2026-10-05', '14:00:00', '16:00:00'),
(5, '2026-10-03', '10:00:00', '12:00:00'),
(6, '2026-10-03', '11:00:00', '13:00:00'),
(7, '2026-09-22', '09:00:00', '10:00:00');

-- ------------------------------------------------------------
-- PRESTAMO_MATERIAL
-- ------------------------------------------------------------
INSERT INTO `prestamo_material` (`ID_Solicitud`, `ID_Material`, `Cantidad`) VALUES
(1, 1, 1),
(1, 6, 2),
(2, 1, 1),
(3, 2, 1),
(8, 1, 1);

-- ------------------------------------------------------------
-- RESERVA_MATERIAL
-- ------------------------------------------------------------
INSERT INTO `reserva_material` (`ID_Solicitud`, `ID_Material`, `Cantidad`) VALUES
(4, 4, 2),
(5, 5, 1),
(6, 5, 1),
(7, 3, 1);

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- ------------------------------------------------------------
INSERT INTO `historial_solicitud` (`ID_Historial`, `Tipo`, `Fecha_Archivo`, `Estado`, `Fecha_Solicitud`, `Fecha_Validacion`, `Motivo_Rechazo`, `CI_Cliente`, `CI_Administrador`) VALUES
(1, 'Prestamo', '2026-08-20 16:00:00', 'Aprobado', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, 'Reserva',  '2026-08-25 12:00:00', 'Aprobado', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002');

SET FOREIGN_KEY_CHECKS = 1;
