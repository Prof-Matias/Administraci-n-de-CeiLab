<?php
/**
 * Controlador de reservas (v8). Responde JSON:
 *   { "success": true,  "data": ... }
 *   { "success": false, "message": "...", "errores": ["..."] }
 *   y, en los avisos de stock, además: "codigo": "SIN_STOCK" | "STOCK_PARCIAL" y "detalle": {...}
 *
 * Cualquier usuario con sesión:  catalogo, calendario
 * Cliente:                       solicitar, mis_reservas, mi_espera
 * Cliente o administrador:       cancelar, modificar, cancelar_espera
 * Administrador:                 listar, aprobar, rechazar, iniciar, finalizar, historial, lista_espera, procesar_espera
 *
 * Las acciones que cambian datos solo se aceptan por POST.
 * Códigos: 401 sin sesión · 403 rol · 405 método · 409 stock parcial (pide confirmar) ·
 *          422 regla de negocio / sin stock · 500 error interno.
 */
require_once __DIR__ . '/../../Config/Guardia.php';
require_once __DIR__ . '/../Modelo/Modelo_Reservas.php';

header('Content-Type: application/json; charset=utf-8');

/** Une $_POST con un cuerpo JSON, para recibir listas (items). */
function entrada() {
    static $datos = null;
    if ($datos === null) {
        $json  = json_decode(file_get_contents('php://input') ?: '', true);
        $datos = array_merge($_POST, is_array($json) ? $json : []);
    }
    return $datos;
}

$action = $_GET['action'] ?? entrada()['action'] ?? '';   // entrada() también lee un cuerpo JSON

$accionesSesion  = ['catalogo', 'calendario'];
$accionesCliente = ['solicitar', 'mis_reservas', 'mi_espera'];
$accionesAmbos   = ['cancelar', 'modificar', 'cancelar_espera'];
$accionesPost    = ['solicitar', 'cancelar', 'modificar', 'cancelar_espera', 'aprobar', 'rechazar', 'iniciar', 'finalizar', 'procesar_espera'];

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

function ok($data = null) {
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
}

/** true/false desde 1, "1", "true", "on", "si"... */
function bandera($v) {
    return filter_var($v, FILTER_VALIDATE_BOOLEAN);
}

/** Atiende la lista de espera antes de mostrar listados. Si falla, no impide mostrar. */
function atenderEspera(Modelo_Reservas $modelo) {
    try {
        $modelo->procesarEspera();
    } catch (Throwable $e) {
        error_log('[Reservas] procesarEspera: ' . $e->getMessage());
    }
}

$ci    = $_SESSION['CI'];
$rol   = strtoupper(trim($_SESSION['Rol'] ?? ''));
$esAdmin = ($rol === 'ADMINISTRADOR');
$in    = entrada();
$idSol = (int)($in['id_solicitud'] ?? $_GET['id'] ?? 0);

try {
    $modelo = new Modelo_Reservas();

    switch ($action) {
        /* ---------- Cualquier usuario con sesión ---------- */
        case 'catalogo':
            // Sin franja: tipos reservables. Con fecha + hora_inicio + hora_fin: stock de esa franja.
            ok($modelo->catalogo($_GET['fecha'] ?? null, $_GET['hora_inicio'] ?? null, $_GET['hora_fin'] ?? null));
            break;

        case 'calendario':
            ok($modelo->calendario($_GET['desde'] ?? null, $_GET['hasta'] ?? null, $_GET['nombre'] ?? null));
            break;

        /* ---------- Cliente ---------- */
        case 'solicitar':
            $items = $in['items'] ?? [];
            if (is_string($items)) {
                $items = json_decode($items, true) ?: [];
            }
            ok($modelo->solicitar($ci, is_array($items) ? $items : [],
                $in['fecha'] ?? '', $in['hora_inicio'] ?? '', $in['hora_fin'] ?? '',
                bandera($in['aceptar_parcial'] ?? false), bandera($in['anotar_espera'] ?? false)));
            break;

        case 'mis_reservas':
            atenderEspera($modelo);
            ok($modelo->listar(['ci_cliente' => $ci, 'estado' => $_GET['estado'] ?? null, 'fecha' => $_GET['fecha'] ?? null]));
            break;

        case 'mi_espera':
            atenderEspera($modelo);
            ok($modelo->listarEspera(['ci_cliente' => $ci]));
            break;

        /* ---------- Cliente o administrador ---------- */
        case 'cancelar':
            $modelo->cancelar($idSol, $ci, $esAdmin);
            ok();
            break;

        case 'modificar':
            $cambios = [];
            foreach (['fecha', 'hora_inicio', 'hora_fin'] as $campo) {
                if (isset($in[$campo]) && $in[$campo] !== '') $cambios[$campo] = $in[$campo];
            }
            if (isset($in['items'])) {
                $items = is_string($in['items']) ? (json_decode($in['items'], true) ?: []) : $in['items'];
                $cambios['items'] = is_array($items) ? $items : [];
            }
            $modelo->modificar($idSol, $ci, $esAdmin, $cambios);
            ok();
            break;

        case 'cancelar_espera':
            $modelo->cancelarEspera((int)($in['id_espera'] ?? 0), $ci, $esAdmin);
            ok();
            break;

        /* ---------- Administrador ---------- */
        case 'listar':
            atenderEspera($modelo);
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

        case 'iniciar':
            $modelo->iniciar($idSol, $ci);
            ok();
            break;

        case 'finalizar':
            $modelo->finalizar($idSol, $ci);
            ok();
            break;

        case 'historial':      // RF47
            ok($modelo->historial(['ci_cliente' => $_GET['ci_cliente'] ?? null, 'fecha' => $_GET['fecha'] ?? null]));
            break;

        case 'lista_espera':
            atenderEspera($modelo);
            ok($modelo->listarEspera(['ci_cliente' => $_GET['ci_cliente'] ?? null, 'estado' => $_GET['estado'] ?? null]));
            break;

        case 'procesar_espera':
            ok(['atendidas' => $modelo->procesarEspera()]);
            break;

        default:
            responderError(400, 'Acción no reconocida.');
    }
} catch (ErrorDisponibilidad $e) {
    // Stock parcial: 409, el cliente debe confirmar. Sin stock: 422.
    http_response_code($e->codigo === ErrorDisponibilidad::STOCK_PARCIAL ? 409 : 422);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errores' => $e->errores,
                      'codigo' => $e->codigo, 'detalle' => $e->detalle], JSON_UNESCAPED_UNICODE);
} catch (ErrorNegocio $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errores' => $e->errores],
                     JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[Reservas] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al procesar la reserva.'],
                     JSON_UNESCAPED_UNICODE);
}
