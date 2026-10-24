<?php
require_once __DIR__ . '/../Modelo/Modelo_Productos.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

require_once __DIR__ . '/../../Config/Guardia.php';

$accionesParaTodos = ['listar', 'ver', 'catalogo'];

if (in_array($action, $accionesParaTodos, true)) {
    exigirSesion();
} else {
    exigirRol('ADMINISTRADOR');
}

try {
    $modelo = new Modelo_Producto();

    function procesarImagen($fileInputName) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $directorioAbsoluto = __DIR__ . '/../uploads/';
            
            if (!file_exists($directorioAbsoluto)) {
                mkdir($directorioAbsoluto, 0777, true);
            }
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension  = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $permitidas, true) || @getimagesize($_FILES[$fileInputName]['tmp_name']) === false) {
                throw new Exception('La foto debe ser una imagen JPG, PNG, GIF o WEBP.');
            }

            $nombreOriginal = $_FILES[$fileInputName]['name'];
            $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
            
            $nombreArchivo = 'prod_' . uniqid() . '.' . strtolower($extension);
            $destino = $directorioAbsoluto . $nombreArchivo;

            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $destino)) {
                return 'uploads/' . $nombreArchivo;
            }
        }
        return null;
    }

    switch ($action) {

        case 'catalogo':
            echo json_encode($modelo->catalogo());
            break;

        case 'listar':
            echo json_encode($modelo->obtenerProductos());
            break;

        case 'ver':
            $id = $_GET['id'] ?? 0;
            echo json_encode($modelo->obtenerProductoPorID($id) ?: []);
            break;

        case 'guardar':
            $nombre = trim($_POST['nombre'] ?? '');
            $tipoControl = strtoupper(trim($_POST['tipo_control'] ?? 'SERIE'));
            $descripcion = $_POST['descripcion'] ?? '';
            $categoria = $_POST['categoria'] ?? '';
            $estado = $_POST['estado'] ?? 'DISPONIBLE';
            $estado = strtoupper(trim($_POST['estado'] ?? 'DISPONIBLE'));

            $rutaImagen = procesarImagen('foto_material') ?: 'uploads/default.png';

            if ($tipoControl === 'SERIE') {
                // Leer números de serie enviados (línea por línea)
                $seriesRaw = $_POST['series'] ?? $_POST['id_serie'] ?? '';
                $lineasSeries = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $seriesRaw)));

                if (empty($lineasSeries)) {
                    echo json_encode(['success' => false, 'message' => 'Debe ingresar al menos un número de serie.']);
                    break;
                }

                // Validar que ninguno de los números de serie exista en la BD
                foreach ($lineasSeries as $serie) {
                    if ($modelo->obtenerProductoPorSerie($serie)) {
                        echo json_encode(['success' => false, 'message' => "El número de serie '{$serie}' ya está registrado en el sistema."]);
                        break 2;
                    }
                }

                // Registrar un producto individual por cada número de serie
                foreach ($lineasSeries as $serie) {
                    $modelo->guardarProducto(
                        $serie,
                        'SERIE',
                        $nombre,
                        $descripcion,
                        $categoria,
                        1, // total
                        1, // disponible
                        $estado,
                        $rutaImagen
                    );
                }

                echo json_encode(['success' => true]);
            } else {
                // Registro por CANTIDAD
                $cantidadTotal = (int)($_POST['cantidad_total'] ?? 0);
                $cantidadDisponible = $cantidadTotal;

                if ($cantidadTotal <= 0) {
                    echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0.']);
                    break;
                }

                if ($modelo->obtenerProductoPorNombre($nombre)) {
                    echo json_encode(['success' => false, 'message' => 'Un producto consumible con ese nombre ya existe.']);
                    break;
                }

                $exito = $modelo->guardarProducto(
                    null,
                    'CANTIDAD',
                    $nombre,
                    $descripcion,
                    $categoria,
                    $cantidadTotal,
                    $cantidadDisponible,
                    $estado,
                    $rutaImagen
                );

                echo json_encode(['success' => (bool)$exito]);
            }
            break;

    case 'actualizar':
        $id = $_POST['id_material'] ?? 0;
        $fotoActual = $_POST['foto_actual'] ?? 'uploads/default.png';
    
        // 1. Obtener el producto actual de la BD
        $productoActual = $modelo->obtenerProductoPorID($id);
        if (!$productoActual) {
        echo json_encode(['success' => false, 'message' => 'El producto no existe.']);
        break;
        }

        $nombreOriginal = $productoActual['nombre']; // Guardamos el nombre actual antes de modificar
        $nuevoNombre    = trim($_POST['nombre'] ?? $nombreOriginal);

        // Los pedidos de préstamo se guardan por nombre: no se renombra un material que está en préstamos aprobados o entregados
        if ($nuevoNombre !== $nombreOriginal) {
            $abiertos = $modelo->solicitudesAbiertasPorNombre($nombreOriginal);
            if ($abiertos) {
                echo json_encode(['success' => false, 'message' => "No se puede cambiar el nombre de «{$nombreOriginal}»: está en los préstamos #"
                    . implode(', #', $abiertos) . ' (aprobados o entregados). Esperá a que se finalicen o se cancelen.']);
                break;
            }
        }
        $descripcion    = $_POST['descripcion'] ?? $productoActual['descripcion'];
        $categoria      = $_POST['categoria'] ?? $productoActual['categoria'];
        $tipoControl    = $productoActual['tipo_control'];
        $idSerieFinal   = null;

        if ($tipoControl === 'SERIE') {
            $idSerieFinal = trim($_POST['id_serie'] ?? $productoActual['id_serie']);

        if (empty($idSerieFinal)) {
            echo json_encode(['success' => false, 'message' => 'El número de serie no puede estar vacío.']);
            break;
            }

        // Un equipo prestado no puede cambiar de número de serie: el préstamo lo identifica por él
        if ($idSerieFinal !== $productoActual['id_serie'] && $productoActual['estado'] === 'PRESTADO') {
            $entregada = $modelo->cantidadEntregada($id);
            echo json_encode(['success' => false, 'message' => "La serie {$productoActual['id_serie']} está prestada"
                . ($entregada['solicitudes'] ? " (préstamo #{$entregada['solicitudes']})" : '')
                . ': no se puede cambiar su número de serie hasta que la devuelvan.']);
            break;
        }

        // Validar duplicado de serie en otro registro
        $productoDuplicado = $modelo->obtenerProductoPorSerie($idSerieFinal, $id);
        if ($productoDuplicado) {
            echo json_encode(['success' => false, 'message' => "El número de serie '{$idSerieFinal}' ya pertenece a otro producto."]);
            break;
        }

        $cantidadTotal = 1;
        $cantidadDisponible = (int)$productoActual['cantidad_disponible'];
        } else {
        $cantidadTotal = (int)($_POST['cantidad_total'] ?? $productoActual['cantidad_total']);
        if ($cantidadTotal < 0) {
            echo json_encode(['success' => false, 'message' => 'La cantidad total no puede ser negativa.']);
            break;
        }

        // Lo entregado en préstamos activos sigue fuera del CeiLab: el total no puede quedar por debajo
        $entregada = $modelo->cantidadEntregada($id);
        if ($cantidadTotal < $entregada['cantidad']) {
            echo json_encode(['success' => false, 'message' => "No se puede bajar el total a {$cantidadTotal}: hay {$entregada['cantidad']} unidades "
                . "entregadas en préstamos activos (#{$entregada['solicitudes']}). Esperá a que las devuelvan o editá esos préstamos."]);
            break;
        }

        // El disponible se recalcula: lo que hay en total menos lo que está entregado.
        // (Antes nunca subía al aumentar el total, y un stock en 0 se quedaba en 0.)
        $cantidadDisponible = $cantidadTotal - $entregada['cantidad'];

        // Si se baja el total, tampoco puede faltar para los préstamos aprobados que todavía no se entregaron
        if ($cantidadTotal < (int)$productoActual['cantidad_total']) {
            $comprometida = $modelo->cantidadComprometida($productoActual['nombre']);
            if ($cantidadDisponible < $comprometida['cantidad']) {
                echo json_encode(['success' => false, 'message' => "Con un total de {$cantidadTotal} quedarían {$cantidadDisponible} disponibles, "
                    . "pero hay {$comprometida['cantidad']} comprometidas en préstamos aprobados (#{$comprometida['solicitudes']})."]);
                break;
            }
        }
        }

        // Procesar imagen
        $nuevaFoto = procesarImagen('foto_material');
        $fotoFinal = $nuevaFoto ? $nuevaFoto : $fotoActual;

        $estadoFinal = isset($_POST['estado']) ? strtoupper(trim($_POST['estado'])) : $productoActual['estado'];

        // 2. Actualizar el registro individual seleccionado
        $exito = $modelo->actualizarProducto(
        $id,
        $nuevoNombre,
        $descripcion,
        $categoria,
        $cantidadTotal,
        $cantidadDisponible,
        $estadoFinal,
        $fotoFinal,
        $idSerieFinal
        );

        // 3. Si la actualización fue exitosa y la opción "Aplicar a todo" está marcada
        $aplicarAlGrupo = isset($_POST['aplicar_al_grupo']) && ($_POST['aplicar_al_grupo'] === '1' || $_POST['aplicar_al_grupo'] === 'on');

        if ($exito && $aplicarAlGrupo) {
        $modelo->actualizarGrupoPorNombre(
            $nombreOriginal,
            $nuevoNombre,
            $descripcion,
            $categoria,
            $fotoFinal
        );
        }

        echo json_encode(['success' => (bool)$exito]);
        break;
    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        $exito = $modelo->eliminarProducto($id);
        echo json_encode(['success' => (bool)$exito]);
        break;

    case 'darDeBaja':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id_material'] ?? null;
        $motivo = trim($data['motivo_baja'] ?? '');

        // Obtener la cédula del usuario responsable desde la sesión
        $ciResponsable = $_SESSION['ci'] ?? $_SESSION['usuario']['ci'] ?? null;

        if (!$id || empty($motivo)) {
        echo json_encode(['success' => false, 'message' => 'Debe ingresar un motivo válido.']);
        break;
        }

        if ($modelo->esProductoComprometido($id)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Operación cancelada: El producto está PRESTADO o reservado.'
        ]);
        break;
        }

        // Se envía la CI del usuario al método
         $resultado = $modelo->darDeBajaConMotivo($id, $motivo, $ciResponsable);
        if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Material dado de baja correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la baja.']);
        }
        break;

    case 'reactivar':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id_material'] ?? null;

        // Obtener la cédula del usuario responsable desde la sesión
        $ciResponsable = $_SESSION['ci'] ?? $_SESSION['usuario']['ci'] ?? null;

        if ($id) {
        // Se envía la CI del usuario al método
        $resultado = $modelo->reactivarProducto($id, $ciResponsable);
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Producto reactivado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo reactivar el producto.']);
        }
        } else {
        echo json_encode(['success' => false, 'message' => 'ID no válido.']);
        }
        break;

    case 'historial':
        $id = $_GET['id'] ?? $_GET['id_material'] ?? 0;
        if ($id) {
        echo json_encode($modelo->obtenerHistorialProducto($id));
        } else {
        echo json_encode([]);
        }
        break;


    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}