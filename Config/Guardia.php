<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function responderError($codigoHttp, $mensaje) {
    http_response_code($codigoHttp);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

function exigirSesion() {
    if (!isset($_SESSION['ci'])) {
        responderError(401, 'Debés iniciar sesión para realizar esta acción.');
    }
}

function exigirRol(...$rolesPermitidos) {
    exigirSesion();
    $rolActual = strtoupper(trim($_SESSION['rol'] ?? ''));
    if (!in_array($rolActual, $rolesPermitidos, true)) {
        responderError(403, 'No tenés permiso para realizar esta acción.');
    }
}

