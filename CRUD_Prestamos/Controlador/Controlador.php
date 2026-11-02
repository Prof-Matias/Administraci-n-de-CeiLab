<?php
/**
 *
 * Acciones del cliente:        catalogo*, solicitar, mis_prestamos, prestamo_bloqueante, cancelar
 * Acciones del administrador:  listar, detalle, aprobar, rechazar,
 *                              unidades_entrega, entregar, devolver, editar, cancelar,
 *                              historial_material
 * (*) el catálogo lo sirve Controlador_Productos (action=catalogo).
 *
 * Las acciones que cambian estado solo se aceptan por POST.
 */
require_once __DIR__ . '/../../Config/Guardia.php';
require_once __DIR__ . '/../Modelo/Modelo_Prestamo.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$accionesCliente = ['solicitar', 'mis_prestamos', 'prestamo_bloqueante'];
$accionesAmbos   = ['cancelar'];
$accionesPost    = ['solicitar', 'cancelar', 'aprobar', 'rechazar', 'entregar', 'devolver', 'editar'];

if (in_array($action, $accionesCliente, true)) {
    exigirRol('CLIENTE');
} elseif (in_array($action, $accionesAmbos, true)) {
    exigirRol('CLIENTE', 'ADMINISTRADOR');
} else {
    exigirRol('ADMINISTRADOR');
}
if (in_array($action, $accionesPost, true) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    responderError(405, 'Esta acción solo se acepta por POST.');
}

/** Une $_POST con un cuerpo JSON, para recibir listas (items, series, devoluciones). */
function entrada() {
    static $datos = null;
    if ($datos === null) {
        $json  = json_decode(file_get_contents('php://input') ?: '', true);
        $datos = array_merge($_POST, is_array($json) ? $json : []);
    }
    return $datos;
}

function ok($data = null) {
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
}

$ci     = $_SESSION['CI'];
$rol    = strtoupper(trim($_SESSION['Rol'] ?? ''));
$in     = entrada();
$idSol  = (int)($in['id_solicitud'] ?? $_GET['id'] ?? 0);

try {
    $modelo = new Modelo_Prestamo();

    switch ($action) {
        /* ---------- Cliente ---------- */
        case 'solicitar':
            $id = $modelo->solicitar($ci, $in['materia'] ?? '', $in['horas_solicitadas'] ?? 0, $in['items'] ?? []);
            ok(['id_solicitud' => $id]);
            break;

        case 'mis_prestamos':
            ok($modelo->listar($_GET['estado'] ?? null, $ci));
            break;

        case 'cancelar':
            $modelo->cancelar($idSol, $ci, $rol === 'ADMINISTRADOR');
            ok();
            break;

        case 'prestamo_bloqueante':
            ok($modelo->prestamoBloqueante($ci));
            break;

        /* ---------- Administrador ---------- */
        case 'listar':
            ok($modelo->listar($_GET['estado'] ?? null));
            break;

        case 'detalle':
            ok($modelo->detalle($idSol));
            break;

        case 'aprobar':
            $modelo->aprobar($idSol, $ci);
            ok();
            break;

        case 'rechazar':
            $modelo->rechazar($idSol, $ci, $in['motivo'] ?? '');
            ok();
            break;

        case 'unidades_entrega':
            ok($modelo->unidadesParaEntregar($idSol));
            break;

        case 'entregar':
            $series = $in['series'] ?? [];
            if (is_string($series)) {               // admite «CEIB-0001, CEIB-0002» o una por línea
                $series = preg_split('/[\s,;]+/', $series, -1, PREG_SPLIT_NO_EMPTY);
            }
            $modelo->entregar($idSol, $ci, $series);
            ok();
            break;

        case 'devolver':
            ok($modelo->devolver($idSol, $ci, $in['items'] ?? []));
            break;

        case 'editar':
            $modelo->editar($idSol, $ci, $in);
            ok();
            break;

        case 'historial_material':
            ok($modelo->historialMaterial((int)($_GET['id_material'] ?? 0)));
            break;
        case 'guardarDesdeAdmin':
            if ($action === 'guardarDesdeAdmin') {
    // 1. Limpiar buffer e inhibir salida HTML para asegurar JSON puro
    if (ob_get_length()) ob_clean();
    ini_set('html_errors', 0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json; charset=utf-8');

    try {
        // 2. Leer la petición enviada por Script.js
        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        if (!$data || empty($data['ci_cliente'])) {
            echo json_encode(['success' => false, 'message' => 'Debes seleccionar un cliente obligatoriamente.']);
            exit();
        }

        $ciCliente = trim($data['ci_cliente']);
        $materia   = !empty($data['materia']) ? trim($data['materia']) : 'General';
        $horas     = !empty($data['horas']) ? (int)$data['horas'] : 2;
        $items     = !empty($data['items']) && is_array($data['items']) ? $data['items'] : [];

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Debes incluir al menos un material en el pedido.']);
            exit();
        }

        // Cédula del administrador logueado en sesión (o por defecto la del sistema)
        $ciAdmin = $_SESSION['usuario']['ci'] ?? $_SESSION['ci'] ?? $ciCliente;

        $modelo = new Modelo_Prestamo();

        // PASO A: Crear la solicitud inicial en estado PENDIENTE
        // (Respetando exactamente los 4 parámetros de tu modelo)
        $idSolicitud = $modelo->solicitar($ciCliente, $materia, $horas, $items);

        if (!$idSolicitud) {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la solicitud de préstamo.']);
            exit();
        }

        // PASO B: Auto-aprobar la solicitud de inmediato (pasa a estado APROBADO)
        if (!empty($data['auto_aprobar'])) {
            $modelo->aprobar($idSolicitud, $ciAdmin);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Préstamo administrativo creado y aprobado exitosamente.'
        ]);

    } catch (ErrorNegocio $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en el servidor: ' . $e->getMessage()
        ]);
    }
    exit();
}

        case 'historial_general':
            ok($modelo->historialGeneral());
            break;

            default:
            responderError(400, 'Acción no reconocida.');
    }
} catch (ErrorNegocio $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errores' => $e->errores],
                     JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    error_log('[Prestamos] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al procesar el préstamo.'],
                     JSON_UNESCAPED_UNICODE);
}


?>