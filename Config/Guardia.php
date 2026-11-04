<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function responderError($codigoHttp, $mensaje) {
    http_response_code($codigoHttp);
    
    // Detectar si la petición espera HTML (navegador) o JSON (AJAX/Fetch)
    $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $aceptaJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

    if (!$esAjax && !$aceptaJson) {
        // Si es una visita directa desde el navegador, redirigir al Index general
        header("Location: ../Index.php?error=desactivada");
        exit;
    }

    // Si es una petición interna de JavaScript, mantener la respuesta JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

function exigirSesion() {
    if (!isset($_SESSION['CI'])) {
        responderError(401, 'Debés iniciar sesión para realizar esta acción.');
    }
}

function exigirRol(...$rolesPermitidos) {
    exigirSesion();
    
    // Normalizamos los roles permitidos y el actual a mayúsculas para evitar errores de tipeo
    $rolesPermitidos = array_map(function($r) {
        return strtoupper(trim($r));
    }, $rolesPermitidos);

    $rolActual = strtoupper(trim($_SESSION['Rol'] ?? ''));
    
    if (!in_array($rolActual, $rolesPermitidos, true)) {
        responderError(403, 'No tenés permiso para realizar esta acción.');
    }
}