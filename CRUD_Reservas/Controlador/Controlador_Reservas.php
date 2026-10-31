<?php
/**
 * Controlador de reservas (v7). Responde JSON:
 *   { "success": true,  "data": ... }
 *   { "success": false, "message": "...", "errores": ["...", ...] }
 *
 * Cualquier usuario con sesión:  equipos, calendario
 * Cliente:                       solicitar, mis_reservas, cancelar
 * Administrador:                 listar, aprobar, rechazar, cancelar
 *
 * Las acciones que cambian estado solo se aceptan por POST.
 */
require_once __DIR__ . '/../../Config/Guardia.php';
require_once __DIR__ . '/../Modelo/Modelo_Reservas.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? entrada()['action'] ?? '';   // entrada() también lee un cuerpo JSON

$accionesSesion  = ['equipos', 'calendario'];
$accionesCliente = ['solicitar', 'mis_reservas'];
$accionesAmbos   = ['cancelar'];
$accionesPost    = ['solicitar', 'cancelar', 'aprobar', 'rechazar'];

// Primero se controla quién es y qué rol tiene; recién después se hace cualquier otra cosa.
if (in_array($action, $accionesSesion, true)) {
    exigirSesion();
} elseif (in_array($action, $accionesCliente, true)) {
    exigirRol('CLIENTE');
} elseif (in_array($action, $accionesAmbos, true)) {
    exigirRol('CLIENTE', 'ADMINISTRADOR');
} else {
    exigirRol('ADMINISTRADOR');
}
if (in_array($action, $accionesPost, true) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    responderError(405, 'Esta acción solo se acepta por POST.');
}

/** Une $_POST con un cuerpo JSON, para recibir listas (equipos). */
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

$ci    = $_SESSION['CI'];
$rol   = strtoupper(trim($_SESSION['Rol'] ?? ''));
$in    = entrada();
$idSol = (int)($in['id_solicitud'] ?? $_GET['id'] ?? 0);

try {
    $modelo = new Modelo_Reservas();

    switch ($action) {
        /* ---------- Cualquier usuario con sesión ---------- */
        case 'equipos':
            ok($modelo->equiposReservables());
            break;

        case 'calendario':
            ok($modelo->calendario($_GET['desde'] ?? null, $_GET['hasta'] ?? null, $_GET['id_material'] ?? null));
            break;

        /* ---------- Cliente ---------- */
        case 'solicitar':
            $equipos = $in['equipos'] ?? [];
            if (!is_array($equipos)) {              // admite «3,5» además de una lista
                $equipos = preg_split('/[\s,;]+/', (string)$equipos, -1, PREG_SPLIT_NO_EMPTY);
            }
            $id = $modelo->solicitar($ci, $equipos, $in['fecha'] ?? '', $in['hora_inicio'] ?? '', $in['hora_fin'] ?? '');
            ok(['id_solicitud' => $id]);
            break;

        case 'mis_reservas':
            ok($modelo->listar(['ci_cliente' => $ci, 'estado' => $_GET['estado'] ?? null, 'fecha' => $_GET['fecha'] ?? null]));
            break;

        case 'cancelar':
            $modelo->cancelar($idSol, $ci, $rol === 'ADMINISTRADOR');
            ok();
            break;

        /* ---------- Administrador ---------- */
        case 'listar':
            ok($modelo->listar([
                'estado'     => $_GET['estado'] ?? null,
                'ci_cliente' => $_GET['ci_cliente'] ?? null,   // RF42
                'fecha'      => $_GET['fecha'] ?? null,        // RF43
            ]));
            break;

        case 'aprobar':
            $modelo->aprobar($idSol, $ci);
            ok();
            break;

        case 'rechazar':
            $modelo->rechazar($idSol, $ci, $in['motivo'] ?? '');
            ok();
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
    error_log('[Reservas] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al procesar la reserva.'],
                     JSON_UNESCAPED_UNICODE);
}
