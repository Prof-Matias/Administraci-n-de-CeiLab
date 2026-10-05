<?php
require_once __DIR__ . '/../Modelo/Modelo_Productos.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

require_once __DIR__ . '/../../Config/Guardia.php';
$accionesParaTodos = ['listar', 'ver'];
if (in_array($action, $accionesParaTodos, true)) {
    exigirSesion();
} else {
    exigirRol('ADMINISTRADOR');
}

try {
    $modelo = new Modelo_Producto();

    // Función auxiliar para subir cualquier tipo de imagen
    function procesarImagen($fileInputName) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $directorioAbsoluto = __DIR__ . '/../uploads/';
            
            // Crea la carpeta 'uploads' si no existe
            if (!file_exists($directorioAbsoluto)) {
                mkdir($directorioAbsoluto, 0777, true);
            }

            $nombreOriginal = $_FILES[$fileInputName]['name'];
            $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
            
            // Genera un nombre único conservando la extensión original (funciona para jpg, png, webp, gif, svg, etc.)
            $nombreArchivo = 'prod_' . uniqid() . '.' . strtolower($extension);
            $destino = $directorioAbsoluto . $nombreArchivo;

            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $destino)) {
                return 'uploads/' . $nombreArchivo; // Ruta relativa para guardar en BD
            }
        }
        return null;
    }

    switch ($action) {

        case 'listar':
            echo json_encode($modelo->obtenerProductos());
            break;

        case 'ver':
            $id = $_GET['id'] ?? 0;
            echo json_encode($modelo->obtenerProductoPorID($id) ?: []);
            break;

        case 'guardar':
            $nombre = $_POST['nombre'] ?? '';

            // 1. Verificación de duplicado por nombre
            if ($modelo->obtenerProductoPorNombre($nombre)) {
                echo json_encode(['success' => false, 'message' => 'El producto ya existe']);
                break;
            }

            // 2. Procesar la imagen recibida
            $rutaImagen = procesarImagen('foto_material');
            if (!$rutaImagen) {
                $rutaImagen = 'uploads/default.png'; // Imagen por defecto si no suben ninguna
            }

            // 3. Insertar en la base de datos
            $exito = $modelo->guardarProducto(
                $nombre,
                $_POST['descripcion'] ?? '',
                $_POST['categoria'] ?? '',
                $_POST['cantidad_total'] ?? 0,
                $_POST['cantidad_disponible'] ?? 0,
                $_POST['estado'] ?? 'Disponible',
                $rutaImagen
            );

            echo json_encode(['success' => (bool)$exito]);
            break;

        case 'actualizar':
            $id = $_POST['id_material'] ?? 0;
            $fotoActual = $_POST['foto_actual'] ?? 'uploads/default.png';

            // Si se subió una nueva imagen, se usa la nueva; de lo contrario, conserva la anterior
            $nuevaFoto = procesarImagen('foto_material');
            $fotoFinal = $nuevaFoto ? $nuevaFoto : $fotoActual;

            $exito = $modelo->actualizarProducto(
                $id,
                $_POST['nombre'] ?? '',
                $_POST['descripcion'] ?? '',
                $_POST['categoria'] ?? '',
                $_POST['cantidad_total'] ?? 0,
                $_POST['cantidad_disponible'] ?? 0,
                $_POST['estado'] ?? 'Disponible',
                $fotoFinal
            );

            echo json_encode(['success' => (bool)$exito]);
            break;

        case 'eliminar':
            $id = $_GET['id'] ?? 0;
            $exito = $modelo->eliminarProducto($id);
            echo json_encode(['success' => (bool)$exito]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}