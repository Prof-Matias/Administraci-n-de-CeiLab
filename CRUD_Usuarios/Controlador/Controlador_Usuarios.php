<?php
require_once __DIR__ . '/../Modelo/Modelo_Usuario.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $modelo = new Modelo_Usuario();

    switch ($action) {

        case 'listar':
            echo json_encode($modelo->obtenerUsuarios());
            break;

        case 'ver':
            $cedula = $_GET['cedula'] ?? '';
            echo json_encode($modelo->obtenerUsuarioPorCI($cedula) ?: []);
            break;

        case 'guardar':
    $cedula = $_POST['cedula'] ?? '';

    // 1. Validar si la cédula ya existe en la base de datos
    $existe = $modelo->obtenerUsuarioPorCI($cedula);
    if ($existe) {
        echo json_encode([
            'success' => false, 
            'message' => 'El usuario ya existe'
        ]);
        break;
    }

    // 2. Si no existe, procedemos a hashear la contraseña y guardar
    $passRaw = $_POST['contrasena'] ?? '';
    $passHash = password_hash($passRaw, PASSWORD_DEFAULT);

    $exito = $modelo->guardarUsuario(
        $cedula,
        $_POST['nombre'] ?? '',
        $_POST['apellido'] ?? '',
        $_POST['correo'] ?? '',
        $passHash,
        $_POST['rol'] ?? '',
        $_POST['especialidad'] ?? ''
    );

    echo json_encode(['success' => (bool)$exito]);
    break;

        case 'actualizar':
            $passInput = $_POST['contrasena'] ?? '';

            // Si el usuario ingresó una contraseña nueva, la hasheamos; si no, conservamos la existente
            if (!empty($passInput)) {
                $passFinal = password_hash($passInput, PASSWORD_DEFAULT);
            } else {
                $passFinal = $_POST['contrasena_actual'] ?? '';
            }

            $exito = $modelo->actualizarUsuario(
                $_POST['cedula'] ?? '',
                $_POST['nombre'] ?? '',
                $_POST['apellido'] ?? '',
                $_POST['correo'] ?? '',
                $passFinal, // <-- Enviamos la contraseña nueva (hasheada) o la anterior
                $_POST['rol'] ?? '',
                $_POST['especialidad'] ?? ''
            );
            echo json_encode(['success' => (bool)$exito]);
            break;

        case 'eliminar':
            $cedula = $_GET['cedula'] ?? '';
            $exito = $modelo->eliminarUsuario($cedula);
            echo json_encode(['success' => (bool)$exito]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}