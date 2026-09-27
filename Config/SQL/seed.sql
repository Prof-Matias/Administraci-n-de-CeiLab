-- ============================================================
-- Sistema de Gestión de Préstamos del CeiLab - CeRP del Este
-- seed.sql — Fase 1: Datos de prueba
-- Contraseña real de TODOS los usuarios de prueba: 123456
-- (hash bcrypt real, compatible con password_verify() de PHP)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIO
-- 2 administradores (DOT) + 4 clientes (2 estudiantes, 2 docentes)
-- ------------------------------------------------------------
INSERT INTO `usuario` (`CI`, `Nombre`, `Apellido`, `Contraseña`, `Email`) VALUES
('11111111', 'Nacho',     'Rodríguez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'joseignaciorodriguezASX@hotmail.com'),
('10000002', 'Martín',    'Rodríguez', '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'martin.rodriguez@cerpdeleste.edu.uy'),
('20000001', 'Ana',       'Pereira',   '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'ana.pereira@cerpdeleste.edu.uy'),
('20000002', 'Carlos',    'Gómez',     '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'carlos.gomez@cerpdeleste.edu.uy'),
('20000003', 'Valentina', 'Silva',     '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'valentina.silva@cerpdeleste.edu.uy'),
('20000004', 'Diego',     'Martínez',  '$2b$10$iz6RuhQBbEtjKcuEA8BP8uSqv.5VSVZIvpo2BK4HvuAl.AZTgJomq', 'diego.martinez@cerpdeleste.edu.uy');

-- ------------------------------------------------------------
-- TELEFONO (multivaluado: Ana tiene 2 teléfonos cargados)
-- ------------------------------------------------------------
INSERT INTO `telefono` (`CI_Usuario`, `Telefono`) VALUES
('11111111', '094190841'),
('20000001', '099222222'),
('20000001', '099222233'),
('20000002', '099333333');

-- ------------------------------------------------------------
-- ADMINISTRADOR
-- ------------------------------------------------------------
INSERT INTO `administrador` (`CI_Administrador`) VALUES
('11111111'),
('10000002');

-- ------------------------------------------------------------
-- CLIENTE
-- ------------------------------------------------------------
INSERT INTO `cliente` (`CI_Cliente`, `Especialidad`, `Rol`) VALUES
('20000001', 'Informática',   'ESTUDIANTE'),
('20000002', 'Matemática',    'DOCENTE'),
('20000003', 'Informática',   'ESTUDIANTE'),
('20000004', 'Informática',   'DOCENTE');

-- ------------------------------------------------------------
-- MATERIAL
-- Cubre las 3 categorías, varios Estado, y un caso sin stock
-- ------------------------------------------------------------
INSERT INTO `material` (`ID_Material`, `Nombre`, `Descripcion`, `Categoria`, `Cantidad_Total`, `Cantidad_Disponible`, `Estado`) VALUES
(1,  'Ceibalita',        'Notebook educativa Ceibal',              'Equipos',                   10, 7, 'Disponible'),
(2,  'Sensor de temperatura', 'Sensor para prácticas de física',        'Gadgets',                   8,  8, 'Disponible'),
(3,  'Pinza cocodrilo',       'Cable con pinzas cocodrilo',             'Gadgets',                   20, 18, 'Disponible'),
(4,  'Batería 9V',            'Batería recargable',                     'Gadgets',                   15, 15, 'Disponible'),
(5,  'Micro:bit',             'Placa programable micro:bit',            'Robótica y Programación',   6,  4, 'Disponible'),
(6,  'LEGO SPIKE',            'Kit de robótica LEGO SPIKE',             'Robótica y Programación',   4,  4, 'Disponible'),
(7,  'Dron educativo',        'Dron para programación de vuelo',        'Robótica y Programación',   2,  0, 'Reservado'),
(8,  'Impresora 3D',          'Impresora 3D del laboratorio',           'Robótica y Programación',   1,  1, 'Disponible');

-- ------------------------------------------------------------
-- SOLICITUD (superclase de Préstamo y Reserva)
-- Cubre estados Pendiente / Aprobado / Rechazado
-- ------------------------------------------------------------
INSERT INTO `solicitud` (`ID_Solicitud`, `Estado`, `Fecha_Solicitud`, `Fecha_Validacion`, `Motivo_Rechazo`, `CI_Cliente`, `CI_Administrador`) VALUES
(1, 'Pendiente', '2026-09-25 09:00:00', NULL,                  NULL,                                  '20000001', NULL),
(2, 'Aprobado',  '2026-09-08 08:30:00', '2026-09-08 09:00:00', NULL,                                  '20000002', '11111111'),
(3, 'Rechazado', '2026-09-20 10:00:00', '2026-09-20 11:00:00', 'Cédula con préstamos vencidos',       '20000003', '11111111'),
(4, 'Pendiente', '2026-09-26 15:00:00', NULL,                  NULL,                                  '20000004', NULL),
(5, 'Aprobado',  '2026-09-24 09:00:00', '2026-09-24 10:00:00', NULL,                                  '20000005', '11111111'),
(6, 'Pendiente', '2026-09-26 16:00:00', NULL,                  NULL,                                  '20000006', NULL),
(7, 'Rechazado', '2026-09-22 09:00:00', '2026-09-22 12:00:00', 'Horario en conflicto con otra reserva','20000007', '11111111'),
(8, 'Aprobado',  '2026-09-14 08:00:00', '2026-09-14 08:30:00', NULL,                                  '20000008', '11111111');

-- ------------------------------------------------------------
-- PRESTAMO (subtipo: solicitudes 1, 2, 3, 8)
-- #2: entregado y sin devolver -> prueba RF25/RF49
-- #8: entregado y devuelto -> prueba de préstamo finalizado
-- ------------------------------------------------------------
INSERT INTO `prestamo` (`ID_Solicitud`, `Materia`, `Horas_Solicitadas`, `Fecha_Entrega`, `Fecha_Devolucion`) VALUES
(1, 'Física',       4, NULL,                  NULL),
(2, 'Matemática',   8, '2026-09-08 08:30:00', NULL),
(3, 'Química',      2, NULL,                  NULL),
(8, 'Informática',  6, '2026-09-15 09:00:00', '2026-09-15 15:00:00');

-- ------------------------------------------------------------
-- RESERVA (subtipo: solicitudes 4, 5, 6, 7)
-- #5 y #6: MISMO equipo (Impresora 3D) con horario superpuesto
-- (11:00-12:00) -> prueba la detección de conflictos (RF40)
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
(1, 4, 2),
(2, 2, 1),
(3, 6, 1),
(8, 1, 1);

-- ------------------------------------------------------------
-- RESERVA_MATERIAL
-- #5 y #6 reservan el mismo material (9 = Impresora 3D)
-- ------------------------------------------------------------
INSERT INTO `reserva_material` (`ID_Solicitud`, `ID_Material`, `Cantidad`) VALUES
(4, 7, 2),
(5, 9, 1),
(6, 9, 1),
(7, 8, 1);

-- ------------------------------------------------------------
-- HISTORIAL_SOLICITUD
-- Registros ya archivados (no corresponden a ninguna fila activa
-- de `solicitud`, simulan préstamos/reservas de meses anteriores)
-- ------------------------------------------------------------
INSERT INTO `historial_solicitud` (`ID_Historial`, `Tipo`, `Fecha_Archivo`, `Estado`, `Fecha_Solicitud`, `Fecha_Validacion`, `Motivo_Rechazo`, `CI_Cliente`, `CI_Administrador`) VALUES
(1, 'Prestamo', '2026-08-20 16:00:00', 'Aprobado', '2026-08-10 09:00:00', '2026-08-10 10:00:00', NULL, '20000001', '10000001'),
(2, 'Reserva',  '2026-08-25 12:00:00', 'Aprobado', '2026-08-15 09:00:00', '2026-08-15 09:30:00', NULL, '20000003', '10000002');

SET FOREIGN_KEY_CHECKS = 1;
