<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Modelo/Modelo_Usuario.php';

$action = $_GET['action'] ?? '';

// Datos de la sesión actual
$rolSesion    = $_SESSION['Rol'] ?? '';
$cedulaSesion = (string)($_SESSION['Cedula'] ?? $_SESSION['CI'] ?? '');

switch ($action) {

    case 'listar':
        $modelo = new Modelo_Usuario();
        echo json_encode($modelo->obtenerUsuarios());
        break;

    case 'ver':
        $cedula = $_GET['cedula'] ?? $_GET['CI'] ?? '';
        $modelo = new Modelo_Usuario();
        $usuario = $modelo->obtenerUsuarioPorCI($cedula);
        echo json_encode($usuario ?: []);
        break;

    case 'guardar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cedula       = trim($_POST['cedula'] ?? $_POST['CI'] ?? '');
            $nombre       = trim($_POST['nombre'] ?? $_POST['Nombre'] ?? '');
            $apellido     = trim($_POST['apellido'] ?? $_POST['Apellido'] ?? '');
            $email        = trim($_POST['correo'] ?? $_POST['email'] ?? $_POST['Email'] ?? '');
            $contrasenia  = $_POST['contrasena'] ?? $_POST['contrasenia'] ?? $_POST['Contraseña'] ?? '';
            $rol          = trim($_POST['rol'] ?? $_POST['Rol'] ?? 'CLIENTE');
            $especialidad = trim($_POST['especialidad'] ?? $_POST['Especialidad'] ?? '');
            $telefonos    = $_POST['telefonos'] ?? [];

            if (empty($cedula) || empty($nombre) || empty($apellido) || empty($contrasenia)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cédula, nombre, apellido y contraseña son campos obligatorios.'
                ]);
                exit();
            }

            $telefonosValidos = [];
            if (is_array($telefonos)) {
                foreach ($telefonos as $tel) {
                    $telLimpio = trim($tel);
                    if (!empty($telLimpio)) {
                        if (!ctype_digit($telLimpio)) {
                            echo json_encode(['success' => false, 'message' => 'Los números de teléfono solo deben contener dígitos.']);
                            exit();
                        }
                        $telefonosValidos[] = $telLimpio;
                    }
                }
            }

            // --- ENCRIPTACIÓN DE CONTRASEÑA ---
            // Procesamos la contraseña plana con el hash de Bcrypt
            $passHash = password_hash($contrasenia, PASSWORD_BCRYPT);

            $modelo = new Modelo_Usuario();
            // Pasamos $passHash en lugar de la variable $contrasenia en texto plano
            $resultado = $modelo->guardarUsuario($cedula, $nombre, $apellido, $email, $passHash, $rol, $especialidad, $telefonosValidos);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Usuario registrado con éxito.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el usuario.']);
            }
        }
        break;

        case 'actualizar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cedulaOriginal = trim($_POST['cedula_original'] ?? $_POST['cedula'] ?? '');
        $cedula         = trim($_POST['cedula'] ?? '');
        $nombre         = trim($_POST['nombre'] ?? '');
        $apellido       = trim($_POST['apellido'] ?? '');
        $email          = trim($_POST['correo'] ?? $_POST['email'] ?? '');
        $contrasenia    = $_POST['contrasena'] ?? $_POST['contrasenia'] ?? '';
        $contraseniaAct = $_POST['contrasena_actual'] ?? '';
        $rol            = trim($_POST['rol'] ?? 'CLIENTE');
        $especialidad   = trim($_POST['especialidad'] ?? '');
        $telefonos      = $_POST['telefonos'] ?? [];

        if (empty($cedula) || empty($nombre) || empty($apellido)) {
            echo json_encode([
                'success' => false,
                'message' => 'Cédula, nombre y apellido son campos obligatorios.'
            ]);
            exit();
        }

        $telefonosValidos = [];
        if (is_array($telefonos)) {
            foreach ($telefonos as $tel) {
                $telLimpio = trim($tel);
                if (!empty($telLimpio)) {
                    if (!ctype_digit($telLimpio)) {
                        echo json_encode(['success' => false, 'message' => 'Los números de teléfono solo deben contener dígitos.']);
                        exit();
                    }
                    $telefonosValidos[] = $telLimpio;
                }
            }
        }

        // --- ENCRIPTACIÓN Y MANEJO DE CONTRASEÑA ---
        $cambiarPass = !empty(trim($contrasenia));
        
        if ($cambiarPass) {
            // Si escribió una contraseña nueva, la encriptamos antes de mandarla al modelo
            $passFinal = password_hash(trim($contrasenia), PASSWORD_BCRYPT);
        } else {
            // Si no cambió la contraseña, mantenemos la contraseña actual (que ya debería venir encriptada)
            $passFinal = $contraseniaAct;
        }

        $modelo = new Modelo_Usuario();
        $resultado = $modelo->actualizarUsuario($cedulaOriginal, $cedula, $nombre, $apellido, $email, $passFinal, $rol, $especialidad, $telefonosValidos, $cambiarPass);

        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el usuario.']);
        }
     }
        break;

    // ---- BAJA LÓGICA: desactivar / reactivar cuenta ----
    case 'cambiarEstado':
    if ($rolSesion !== 'ADMINISTRADOR') {
        echo json_encode(['success' => false, 'message' => 'No tenés permisos para esta acción.']);
        exit();
    }

    $input  = json_decode(file_get_contents('php://input'), true);
    $cedula = preg_replace('/[^0-9]/', '', $input['cedula'] ?? '');
    $estado = strtoupper(trim($input['estado'] ?? ''));

    if ($cedula === '' || !in_array($estado, ['ACTIVA', 'INACTIVA'], true)) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
        exit();
    }

    // Un admin no puede desactivar su propia cuenta
    if ($estado === 'INACTIVA' && $cedula === $cedulaSesion) {
        echo json_encode(['success' => false, 'message' => 'No podés desactivar tu propia cuenta.']);
        exit();
    }

    $modelo = new Modelo_Usuario();

    // ── NUEVA RESTRICCIÓN: Validar si intenta desactivar y tiene préstamos activos ──
    if ($estado === 'INACTIVA') {
        $prestamoActivo = $modelo->obtenerPrestamoActivo($cedula);
        if ($prestamoActivo) {
            $idPrestamo = $prestamoActivo['id_solicitud'];
            echo json_encode([
                'success' => false, 
                'message' => "No se puede dar de baja a este usuario porque tiene préstamos pendientes (ID del préstamo: #{$idPrestamo})."
            ]);
            exit();
        }
    }
    // ─────────────────────────────────────────────────────────────────────────────

    $ok = $modelo->cambiarEstadoCuenta($cedula, $estado);

    echo json_encode([
        'success' => $ok,
        'message' => $ok
            ? ($estado === 'INACTIVA' ? 'Cuenta desactivada.' : 'Cuenta reactivada.')
            : 'No se pudo cambiar el estado de la cuenta.'
    ]);
    break;

    
}