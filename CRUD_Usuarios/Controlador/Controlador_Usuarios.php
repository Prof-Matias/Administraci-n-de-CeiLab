<?php
/**
 * Controlador_Usuarios.php
 * 
 * Controlador principal para la gestión de usuarios.
 * Recibe las peticiones HTTP (GET, POST o JSON via fetch), valida los datos de entrada,
 * interactúa con el modelo (Modelo_Usuario.php) y responde siempre en formato JSON.
 */

// Se incluye la definición de la clase Modelo_Usuario para interactuar con la base de datos
require_once __DIR__ . '/../Modelo/Modelo_Usuario.php';

require_once __DIR__ . '/../../Config/Guardia.php';
exigirRol('ADMINISTRADOR');

// Se establece la cabecera de respuesta como JSON con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');

/* ==============================================================================
 * 1. CAPTURA Y FUSIÓN DE DATOS DE LA PETICIÓN
 * ==============================================================================
 * Permite recibir parámetros enviado tanto por GET, POST (FormData) o payloads JSON (fetch raw).
 */
$inputJSON = json_decode(file_get_contents('php://input'), true) ?? [];
$request   = array_merge($_GET, $_POST, $inputJSON);

// Capturamos la acción a ejecutar enviada desde el cliente
$action = $request['action'] ?? '';

try {
    // Instanciamos el modelo de datos de usuarios
    $modelo = new Modelo_Usuario();

    // Evaluamos la acción requerida
    switch ($action) {

        /* ----------------------------------------------------------------------
         * CASO: LISTAR USUARIOS
         * Retorna el arreglo directo de usuarios para renderizar la grilla en JS.
         * ---------------------------------------------------------------------- */
        case 'listar':
            $usuarios = $modelo->obtenerUsuarios();
            
            // Retornamos directamente el listado (o array vacío) que espera la grilla
            echo json_encode($usuarios ?: []);
            break;

        /* ----------------------------------------------------------------------
         * CASO: VER USUARIO INDIVIDUAL
         * Busca un usuario por Cédula y retorna su objeto directamente para completar modales.
         * ---------------------------------------------------------------------- */
        case 'ver':
            $cedula = trim($request['cedula'] ?? '');

            // Validación: Si no se especifica la cédula, devolvemos objeto/array vacío
            if (empty($cedula)) {
                echo json_encode([]);
                break;
            }

            $usuario = $modelo->obtenerUsuarioPorCI($cedula);
            echo json_encode($usuario ?: []);
            break;

        /* ----------------------------------------------------------------------
         * CASO: GUARDAR / REGISTRAR UN NUEVO USUARIO
         * Valida campos obligatorios, duplicados y aplica hash a la contraseña.
         * ---------------------------------------------------------------------- */
        case 'guardar':
            // Lectura y saneamiento de entradas
            $cedula       = trim($request['cedula'] ?? '');
            $nombre       = trim($request['nombre'] ?? '');
            $apellido     = trim($request['apellido'] ?? '');
            $correo       = trim($request['correo'] ?? '');
            $passRaw      = $request['contrasena'] ?? '';
            $rol          = trim($request['rol'] ?? '');
            $especialidad = trim($request['especialidad'] ?? '');

            // 1. Validar que los campos obligatorios contengan información
            if (empty($cedula) || empty($nombre) || empty($apellido) || empty($passRaw)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cédula, nombre, apellido y contraseña son campos obligatorios'
                ]);
                break;
            }

            // 2. Verificar que no exista un usuario registrado con la misma Cédula
            $existe = $modelo->obtenerUsuarioPorCI($cedula);
            if ($existe) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'El usuario con esta cédula ya se encuentra registrado'
                ]);
                break;
            }

            // 3. Generar hash seguro para la contraseña
            $passHash = password_hash($passRaw, PASSWORD_DEFAULT);

            // 4. Guardar mediante el modelo
            $exito = $modelo->guardarUsuario(
                $cedula,
                $nombre,
                $apellido,
                $correo,
                $passHash,
                $rol,
                $especialidad
            );

            echo json_encode([
                'success' => (bool)$exito,
                'message' => $exito ? 'Usuario registrado correctamente' : 'Error al registrar el usuario'
            ]);
            break;

        /* ----------------------------------------------------------------------
         * CASO: ACTUALIZAR DATOS DE UN USUARIO EXISTENTE
         * Permite modificar datos del usuario incluyendo la propia cédula (Clave Primaria).
         * ---------------------------------------------------------------------- */
        case 'actualizar':
            // Capturamos la cédula original (para el WHERE de la consulta) y la cédula nueva
            $cedulaOriginal = trim($request['cedula_original'] ?? $request['cedula'] ?? '');
            $cedulaNueva    = trim($request['cedula'] ?? '');
            $passInput      = $request['contrasena'] ?? '';
            $passActual     = $request['contrasena_actual'] ?? '';

            // Validar la presencia de la clave de identificación
            if (empty($cedulaOriginal) || empty($cedulaNueva)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'La cédula es requerida para actualizar los datos'
                ]);
                break;
            }

            /*
             * Manejo de la Contraseña:
             * Si el usuario escribió una clave nueva se encripta de nuevo.
             * Si el campo quedó vacío se conserva el hash que ya poseía.
             */
            if (!empty($passInput)) {
                $passFinal = password_hash($passInput, PASSWORD_DEFAULT);
            } else {
                $passFinal = $passActual;
            }

            // Enviamos tanto la Cédula Original como la Nueva al Modelo para procesar el UPDATE
            $exito = $modelo->actualizarUsuario(
                $cedulaOriginal,
                $cedulaNueva,
                trim($request['nombre'] ?? ''),
                trim($request['apellido'] ?? ''),
                trim($request['correo'] ?? ''),
                $passFinal,
                trim($request['rol'] ?? ''),
                trim($request['especialidad'] ?? '')
            );

            echo json_encode([
                'success' => (bool)$exito,
                'message' => $exito ? 'Usuario actualizado correctamente' : 'Error al actualizar el usuario'
            ]);
            break;

        /* ----------------------------------------------------------------------
         * CASO: ELIMINAR USUARIO
         * ---------------------------------------------------------------------- */
        case 'eliminar':
            $cedula = trim($request['cedula'] ?? '');

            if (empty($cedula)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'La cédula es requerida para eliminar el usuario'
                ]);
                break;
            }

            $exito = $modelo->eliminarUsuario($cedula);

            echo json_encode([
                'success' => (bool)$exito,
                'message' => $exito ? 'Usuario eliminado correctamente' : 'Error al eliminar el usuario'
            ]);
            break;

        /* ----------------------------------------------------------------------
         * CASO POR DEFECTO
         * ---------------------------------------------------------------------- */
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Acción no válida o no especificada'
            ]);
            break;
    }

} catch (Throwable $e) {
    /* ==============================================================================
     * CAPTURA GLOBAL DE EXCEPCIONES
     * ==============================================================================
     * Retorna cualquier falla o error no esperado en formato JSON estándar.
     */
    echo json_encode([
        'success' => false, 
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}