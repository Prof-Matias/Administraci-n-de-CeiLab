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