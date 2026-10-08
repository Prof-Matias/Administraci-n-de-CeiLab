<?php
/**
 * Controlador_Usuarios.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Modelo/Modelo_Usuario.php';

$action = $_GET['action'] ?? '';

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
            $cedula      = trim($_POST['cedula'] ?? $_POST['CI'] ?? '');
            $nombre      = trim($_POST['nombre'] ?? $_POST['Nombre'] ?? '');
            $apellido    = trim($_POST['apellido'] ?? $_POST['Apellido'] ?? '');
            $email       = trim($_POST['correo'] ?? $_POST['email'] ?? $_POST['Email'] ?? '');
            $contrasenia = $_POST['contrasena'] ?? $_POST['contrasenia'] ?? $_POST['Contraseña'] ?? '';
            $rol         = trim($_POST['rol'] ?? $_POST['Rol'] ?? 'CLIENTE');
            $especialidad= trim($_POST['especialidad'] ?? $_POST['Especialidad'] ?? '');

            if (empty($cedula) || empty($nombre) || empty($apellido) || empty($contrasenia)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cédula, nombre, apellido y contraseña son campos obligatorios.'
                ]);
                exit();
            }

<<<<<<< Updated upstream
            $modelo = new Modelo_Usuario();
            $resultado = $modelo->guardarUsuario($cedula, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad);
=======
            // 2. Verificar que no exista un usuario registrado con la misma Cédula
            $existe = $modelo->obtenerUsuarioPorCI($cedula);
            if ($existe) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'El Usuario con esta cédula ya se encuentra registrado'
                ]);
                break;
            }
>>>>>>> Stashed changes

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Usuario guardado con éxito.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar. La cédula o el email ya podrían estar registrados.']);
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

            if (empty($cedula) || empty($nombre) || empty($apellido)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cédula, nombre y apellido son campos obligatorios.'
                ]);
                exit();
            }

            $passFinal = !empty($contrasenia) ? $contrasenia : $contraseniaAct;
            $cambiarPass = !empty($contrasenia);

            $modelo = new Modelo_Usuario();
            $resultado = $modelo->actualizarUsuario($cedulaOriginal, $cedula, $nombre, $apellido, $email, $passFinal, $rol, $especialidad, $cambiarPass);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado con éxito.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el usuario.']);
            }
        }
        break;

    case 'eliminar':
        $cedula = $_GET['cedula'] ?? $_POST['cedula'] ?? '';
        if (!empty($cedula)) {
            $modelo = new Modelo_Usuario();
            $resultado = $modelo->eliminarUsuario($cedula);
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}